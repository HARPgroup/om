# Produce a habitat alteration plot
# NOte: model element must have custom1 = cova_aquaticbio
# things that can be stored on the model -> reports -> habitat property:
# - base_flow_column: (default: auto, will calc via wd_mgd/ps_mgd and Qout)
# - flow_column: (default Qout)
# - habitat_grid: wua (default name of habitat matrix prop)
# - target_cols: which columns to show: (default: all)
# 

#----------------------------------------------
#site <- "http://deq1.bse.vt.edu/d.dh"    #Specify the site of interest, either d.bet OR d.dh
#----------------------------------------------
# Load Libraries
suppressPackageStartupMessages(library(ggplot2))
suppressPackageStartupMessages(library(stringr))
suppressPackageStartupMessages(library(hydrotools))
basepath='/var/www/R';
source(paste(basepath,'config.R',sep='/'))
source(paste0(github_location,"/om/R/habitat/ifim_wua_change_plot.R"))
source(paste0(github_location,"/om/R/habitat/hab_ts_functions.R"))
source(paste0(github_location,"/om/R/habitat/hab_plot.R"))
library(hydrotools)
save_directory <-  "/var/www/html/data/proj3/out"

# Read Args
# argst=c(7420787, 337646, 600, 1000, "Qstudy_R648", "wua_R648")
# argst=c(7420787, 337646, 600, 400, "Qstudy_R648", "wua_R648")
argst <- commandArgs(trailingOnly=T)
pid <- as.integer(argst[1])
elid <- as.integer(argst[2])
runid <- as.integer(argst[3])
if (length(argst) > 3) {
  flow_pct <- as.numeric(argst[4])
}
# Now, before we handle xtra args we check to load defaults
# load the model in question
model <- RomProperty$new(ds, list(pid=pid),TRUE)
# GETTING SCENARIO PROPERTY FROM VA HYDRO
scen.propname<-paste0('runid_', runid)
sceninfo <- list(
  varkey = 'om_scenario',
  propname = scen.propname,
  featureid = pid,
  entity_type = "dh_properties",
  bundle = "dh_properties"
)
scenprop <- RomProperty$new( ds, sceninfo, TRUE)
scenprop$save(TRUE)
# Now, load custom scenario/object info
report_defaults <- model$get_prop("reports")
scenario_report_defaults <- scenprop$get_prop("reports")

# Check for defaults (should code up a function for this)
check_defaults <- function(
    propname, default_value, scenario_report_defaults, report_defaults
  ) {
  custom_value = default_value
  if (typeof(scenario_report_defaults) %in% c('environment', 'list')) {
    check_custom <- scenario_report_defaults$get_prop(propname)
    if (!is.logical(check_custom) && !is.na(check_custom$pid)) {
      custom_value = check_custom$propvalue
    } else {
      if (typeof(scenario_report_defaults) %in% c('environment', 'list')) {
        check_custom <- report_defaults$get_prop(propname)
        if (!is.logical(check_custom) && !is.na(check_custom$pid)) {
          custom_value = check_custom$propvalue
        }
      }
    }
  }
  return(custom_value)
}

runid_base = check_defaults("runid_base", 0, scenario_report_defaults, report_defaults)
Qcol = check_defaults("habitat_Qcol", "Qreach", scenario_report_defaults, report_defaults)
wua_name = check_defaults("habitat_wua_name", "wua", scenario_report_defaults, report_defaults)
flow_pct = check_defaults("habitat_flow_pct", 0.25, scenario_report_defaults, report_defaults)
if (length(argst) > 3) {
  runid_base = as.integer(argst[4])
}
if (length(argst) > 4) {
  Qcol = as.character(argst[5])
}
if (length(argst) > 5) {
  wua_name = as.character(argst[6])
}

# load run results
finfo <- fn_get_runfile_info(elid, runid,37, site= omsite)
remote_url <- as.character(finfo$remote_url)
dat_base <- fn_get_runfile(elid, runid_base, site= omsite,  cached = FALSE)
dat <- fn_get_runfile(elid, runid, site= omsite,  cached = FALSE)
# grab model run period before removing warmup period
model_run_start <- min(dat$thisdate) 
model_run_end <- max(dat$thisdate)
# eliminate warmup period
dat <- fn_remove_model_warmup(dat)
sdate <- min(dat$thisdate)
edate <- max(dat$thisdate)
cols <- names(dat)
mode(dat) <- 'numeric'
mode(dat_base) <- 'numeric'
dat <- as.data.frame(dat)
dat_base <- as.data.frame(dat_base)

# 

# Create table for flow comparison
dat_all_flows <- sqldf(
  paste0(
    "select a.", Qcol," as Qout, b.", Qcol," as Qbaseline, 
       a.year, a.month, a.day 
     from dat as a
     left outer join dat_base as b
     on (a.year = b.year and a.month = b.month and a.day = b.day)
     where a.", Qcol, " is not null
     and b.", Qcol, " is not null
     order by a.year, a.month, a.day
    "
  )
)
dat_all_flows$Date <- as.Date(
  paste(as.integer(dat_all_flows$year), as.integer(dat_all_flows$month), as.integer(dat_all_flows$day), 
      sep="/")
)
# not yet ready everywhere since all components do NOT have the ws and ps cumulative variables
# really, this should load the zero run, and compare a similar time period.
# If no run zero, we can do this:
if ( is.logical(runid_base) ) {
  dat$Qbaseline <- dat$Qreach +
    (dat$wd_cumulative_mgd - dat$ps_cumulative_mgd ) * 1.547
}
#ifim_featureid = 397293 # Posey Hollow
ifim_nfs_model <- ds$get_json_prop(pid)
ifim_dataframe <- ifim_nfs_model[[wua_name]]$matrix$value
#ifim_featureid = 397294 # Rt 648
#ifim_featureid = 397294 # Rt 648
#ifim_dataframe <- vahydro_prop_matrix(ifim_featureid, 'dh_feature','ifim_habitat_table')

targets <- ifim_dataframe[1,]
WUA.df <- ifim_dataframe[-1,]
colnames(WUA.df) <- targets
# this is only used in labeling 
ifim_da_sqmi = 737 # need to change to dynamic

hab_alt_plot <- pothab_plot(
  WUA.df, dat_all_flows, "Qbaseline", "Qout",
  flow_pct, ifim_da_sqmi,
  "Posey Hollow", "Current"
)

hab_alt_plot$labels$title = paste("Change in habitat for scenario",runid, "vs scenario",runid_base)
#Image saving & naming
fname <- paste(
  save_directory,
  paste0('habitat_',flow_pct,'.png'),
  sep = '/'
)

furl <- paste(
  save_url,paste0('habitat_',flow_pct,'.png'),
  sep = '/'
)
ggsave(fname, plot = hab_alt_plot, width = 7, height = 5.5)
message(paste("Saved file: ", fname, "with URL", furl))
vahydro_post_metric_to_scenprop(scenprop$pid, 'dh_image_file', furl, paste0('habitat_',flow_pct), 0.0, ds)


print(1) # to act as positive returning function