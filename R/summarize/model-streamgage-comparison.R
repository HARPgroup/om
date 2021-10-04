library(stringr)
# SETTING UP BASEPATH AND SOURCING FUNCTIONS
#----------------------------------------------
# site <- "http://deq2.bse.vt.edu/d.dh"    #Specify the site of interest, either d.bet OR d.dh
# this is set in config.R
#----------------------------------------------
# Load Libraries
basepath='/var/www/R';
source(paste(basepath,'config.R',sep='/'))
library(dataRetrieval)
library(hydrotools)
ds <- RomDataSource$new("http://deq1.bse.vt.edu/d.dh", rest_uname)
ds$get_token(rest_pw)

save_directory <-  "/var/www/html/data/proj3/out"

# Read Args
# if testing:
# riv.seg = 'PM7_4580_4820_difficult_run'
# runid = 2
# gage_number = '01646000'
# mod.scenario = 'vahydro-1.0'
argst <- commandArgs(trailingOnly=T)
riv.seg <- as.character(argst[1])
runid <- as.integer(argst[2]) # what to store this as
gage_number <- as.character(argst[3])
# scenario for the model to compare the gage to vahydro-1.0, CFBASE30Y20180615, p532cal_062211, ...
mod.scenario <- as.character(argst[4])

# ESSENTIAL INPUTS
dat.source1 <- 'gage' # cbp_model
# If a gage is used -- all data is trimmed to gage timeframe.  Otherwise, start/end date defaults
# can be found in the gage.timespan.trimmed loop.

# Inputs if using CBP Model -- otherwise, can ignore
mod.phase <- 'p6/p6_gb604' #or "p532c-sova" (phase 5)
if (mod.scenario == 'p532cal_062211') {
  mod.phase <- "p532c-sova"# (phase 5)
}
site.or.server <- 'site'

mrun_name <- paste0('runid_', runid)
# run name for gage
grun_name <- mrun_name
# Inputs if using USGS gage -- otherwise, can ignore
message(paste("Retrieving timespan for usgs", gage_number))
gage_timespan <- get.gage.timespan(gage_number)
message(paste("Retrieving Gage Info for usgs", gage_number))
gage <- try(readNWISsite(gage_number))

# Load the VAHydro watershed entity via a riversegment based hydrocode (useful in testing)
hydrocode = paste0("vahydrosw_wshed_", riv.seg);
message(paste("searching for watershed", riv.seg,"with hydrocode", hydrocode))
feature <- RomFeature$new(ds,list(hydrocode=hydrocode),TRUE)
hydroid = feature$hydroid

# any allow om_water_model_node, om_model_element as varkeys
mm <- RomProperty$new(
  ds,list(
    featureid = hydroid,
    entity_type = 'dh_feature',
    propcode = mod.scenario
  ), TRUE)
gm <- RomProperty$new(
  ds,list(
    featureid = hydroid,
    entity_type = 'dh_feature',
    propcode = 'usgs-1.0'
  ), TRUE)

if (is.na(gm$pid)) {
  # create new model
  gm$propname <- paste("USGS", gage_number, gage$station_nm, '- Weighted')
  gm$save(TRUE)

}
gage.scenprop.pid <- gm$pid

# run name for model
# load the model data
if (substr(mod.scenario,1,7) == 'vahydro') {
  message("Grabbing vahyro model data")
  elid <- ds$get_prop(slist(propname))
  rawdat <- fn_get_runfile(elid, runid, site = omsite,  cached = FALSE);
  model_data <- vahydro_format_flow_cfs(rawdat)
  # try model timeseries local_channel_area and area_sqmi
  da = NULL
  if (!is.na(mean(as.numeric(rawdat$area_sqmi)))) {
    da <- mean(as.numeric(rawdat$area_sqmi))
  } else if (!is.na(mean(as.numeric(rawdat$local_channel_area)))) {
    da <- mean(as.numeric(rawdat$local_channel_area))
  }
} else {
  # this is cbp model, different import procedure
  message("Grabbing CBP model data")
  model_data <- model_import_data_cfs(riv.seg, mod.phase, mod.scenario, NULL, NULL)
  # try to get da from the feature
  da = NULL
  daprop <-  RomProperty$new(ds, list (
    propname = 'wshed_drainage_area_sqmi',
    featureid = hydroid,
    entity_type = 'dh_feature'
  ), TRUE)
  da <- as.numeric(daprop$propvalue)
}
start.date <- min(model_data$date)
end.date <- max(model_data$date)
gage_data <- gage_import_data_cfs(gage_number, start.date, end.date)

wscale = 1.0
# now, if da is not NULL we scale, otherwise assume gage area and watershed area are the same
if (!is.null(da)) {
  wscale <- as.numeric(as.numeric(da) / as.numeric(gage$drain_area_va))
  gage_data$flow <- as.numeric(gage_data$flow) * wscale
  gda <- RomProperty$new(
    ds,list(
      featureid = hydroid,
      entity_type = 'dh_feature',
      varkey = 'om_class_Constant',
      propname = 'drain_area_va',
      propvalue = as.numeric(gage$drain_area_va)
    ), TRUE
  )
  wsc <- RomProperty$new(
    ds,list(
      featureid = hydroid,
      entity_type = 'dh_feature',
      varkey = 'om_class_Constant',
      propname = 'wscale',
      propvalue = as.numeric(wscale)
    ), TRUE
  )
  wsc$save(TRUE)
}

gage.timespan.trimmed <- FALSE
if (min(gage_data$date) > min(model_data$date)) {
  gage.timespan.trimmed <- TRUE #or FALSE
  start.date <- min(gage_data$date)
}
if (max(model_data$date) > max(gage_data$date)) {
  gage.timespan.trimmed <- TRUE #or FALSE
  end.date <- max(gage_data$date)
}

# Now trim the series
gage_data_formatted <- vahydro_trim_for_iha(gage_data, start.date, end.date)
model_data_formatted  <- vahydro_trim_for_iha(model_data, start.date, end.date)

if (gage.timespan.trimmed == TRUE) {
  # timespan, we need to tim the modeled timespan, and create a new name
  # "[run_name]_gage_timespan"
  # creates a separate scenario specifically to hold this trimmed time span
  mrun_name <- paste0(mrun_name, '_gage_timespan')
  message(paste("Timespans do not overlap, scenario saved as", mrun_name, "with timespan", start.date, "to", end.date))
}
mmodel_run <- om_get_set_model_run(mm$pid, mrun_name, site, token)
gmodel_run <- om_get_set_model_run(gm$pid, grun_name, site, token)
# stash scaling factor
scale_info = list(
  featureid = gmodel_run$pid,
  varkey='om_class_Constant',
  propname = 'scale_factor',
  propvalue = wscale,
  entity_type = 'dh_properties'
)
scaleprop <- postProperty(scale_info, site, scaleprop)

all_flow_metrics_2_vahydro(gmodel_run$pid, gage_data_formatted, ds)
# do we need to do this if the model has already been summarized
# Test this?
# ts <- as.POSIXct(model_data_formatted$date,origin="1970-01-01")
# model_data_formatted <- zoo(model_data_formatted, order.by = ts)
# all_flow_metrics_2_vahydro(mmodel_run$pid, as.zoo(model_data_formatted), token)
# instead of this
all_flow_metrics_2_vahydro(mmodel_run$pid, model_data_formatted, ds)

message("Stream gage comparison complete.")
message(paste("Scale adjustment for gage record:", wscale))
if (gage.timespan.trimmed == TRUE) {
  message(paste("Timespans do not overlap, scenario saved as", mrun_name, "with timespan", start.date, "to", end.date))
}
