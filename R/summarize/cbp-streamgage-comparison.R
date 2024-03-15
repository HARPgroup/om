library(stringr)
# SETTING UP BASEPATH AND SOURCING FUNCTIONS
#----------------------------------------------
site <- "http://deq2.bse.vt.edu/d.dh"    #Specify the site of interest, either d.bet OR d.dh
#----------------------------------------------
# Load Libraries
basepath='/var/www/R';
source(paste(basepath,'config.R',sep='/'))
library(dataRetrieval)

save_directory <-  "/var/www/html/data/proj3/out"

# given a river segment and a gage #, and a runid,
# create a surrogate gage model attached to river segment
# Read Args
argst <- commandArgs(trailingOnly=T)
riv.seg <- as.character(argst[1])
gage_number <- as.character(argst[2])
runid <- as.integer(argst[3])

# ESSENTIAL INPUTS
dat.source1 <- 'gage' # cbp_model
dat.source2 <- 'vahydro'
site <- "http://deq2.bse.vt.edu/d.dh"
# If a gage is used -- all data is trimmed to gage timeframe.  Otherwise, start/end date defaults
# can be found in the gage.timespan.trimmed loop.

# Inputs if using CBP Model -- otherwise, can ignore
mod.phase <- 'p6/p6_gb604' #or "p532c-sova" (phase 5)
mod.scenario1 <- 'CFBASE30Y20180615' #or 'CBASE1808L55CY55R45P50R45P50Y' (climate change) 'CFBASE30Y20180615' (base) 'CBASE1808L55CY55R45P10R45P10Y' (climate change 10%) 'CBASE1808L55CY55R45P90R45P90Y' (climate change 90%)
mod.scenario2 <- mod.scenario1
site.or.server <- 'site'

# Load the VAHydro watershed entity via a riversegment based hydrocode (useful in testing)
hydrocode = paste0("vahydrosw_wshed_", riv.seg);
feature = om_get_feature(site, hydrocode, 'watershed', 'vahydro')
hydroid = feature$hydroid
mm <- om_get_model(site, hydroid)
elid <- om_get_model_elementid(site, mm$pid)

# Inputs if using USGS gage -- otherwise, can ignore
message(paste("Retrieving timespan for usgs", gage_number))
gage_timespan <- get.gage.timespan(gage_number)
message(paste("Retrieving Gage Info for usgs", gage_number))
gage <- try(readNWISsite(gage_number))
gage.title <- paste("USGS", gage_number, gage$station_nm, '- Weighted')

# load the model scenario, if the gage timespan does not span the entire modeling 
# timespan, we need to trim the modeled timespan, and create a new runid
# "runid_[run id]_gage_timespan"
rawdat <- fn_get_runfile(elid, runid, site = omsite,  cached = FALSE);
model_data <- vahydro_format_flow_cfs(rawdat)
start.date <- min(model_data$date)
end.date <- max(model_data$date)
gage_data <- gage_import_data_cfs(gage_number, start.date, end.date)
# try model timeseries local_channel_area and area_sqmi
da = NULL
if (!is.na(mean(as.numeric(rawdat$area_sqmi)))) {
  da <- mean(as.numeric(rawdat$area_sqmi))
} else if (!is.na(mean(as.numeric(rawdat$local_channel_area)))) {
  da <- mean(as.numeric(rawdat$local_channel_area))
}

# now, if da is not NULL we scale, otherwise assume gage area and watershed area are the same
if (!is.null(da)) {
  wscale <- as.numeric(as.numeric(da) / as.numeric(gage$drain_area_va))
  gage_data$flow <- as.numeric(gage_data$flow) * wscale
}
# dfd

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

if (dat.source1 == 'gage' || dat.source2 == 'gage') {
  post.gage.scen.prop(riv.seg, gage.title, site, token)
  # should store gage weighting factor as property
}

# Changes graph labels automatically
if (dat.source1 == 'vahydro') {
  cn1 <- paste('VAhydro_runid_', runid, sep = '')
} else if (dat.source1 == 'gage') {
  cn1 <- paste('USGS_', gage_number, sep = '')
} else if (dat.source1 == 'cbp_model') {
  cn1 <- paste('CBP_scen_ ', mod.scenario1, sep = '')
}

if (dat.source2 == 'vahydro') {
  cn2 <- paste('VAhydro_runid_', runid, sep = '')
} else if (dat.source2 == 'gage') {
  cn2 <- paste('USGS_', gage_number, sep = '')
} else if (dat.source2 == 'cbp_model') {
  cn2 <- paste('CBP_scen_ ', mod.scenario2, sep = '')
}


if (gage.timespan.trimmed == TRUE) {
  model.scenprop.pid <- get.gage.timespan.scen.prop(riv.seg, runid, site, token)
} else {
  model.scenprop.pid <- get.scen.prop(riv.seg, 'vahydro-1.0', 'vahydro', runid, start.date, end.date, site, token)
}
gage.scenprop.pid <- get.scen.prop(riv.seg, 'usgs-1.0', 'gage', runid, start.date, end.date, site, token)

# POSTING METRICS
all_flow_metrics_2_vahydro(gage.scenprop.pid, gage_data_formatted, token)
all_flow_metrics_2_vahydro(model.scenprop.pid, model_data_formatted, token)
 

print(1) # to act as positive returning function