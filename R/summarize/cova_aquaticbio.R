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
suppressPackageStartupMessages(library(hydrotools))
basepath='/var/www/R';
source(paste(basepath,'config.R',sep='/'))
source(paste("https://raw.githubusercontent.com/HARPgroup/r-dh-ecohydro",'master/Analysis/habitat','ifim_wua_change_plot.R',sep='/'))
source(paste("https://raw.githubusercontent.com/HARPgroup/r-dh-ecohydro",'master/Analysis/habitat','hab_ts_functions.R',sep='/'))
# Load Libraries
basepath='/var/www/R';
source(paste(basepath,'config.R',sep='/'))
save_directory <-  "/var/www/html/data/proj3/out"
library(hydrotools)

# Read Args
argst <- commandArgs(trailingOnly=T)
pid <- as.integer(argst[1])
elid <- as.integer(argst[2])
runid <- as.integer(argst[3])

finfo <- fn_get_runfile_info(elid, runid,37, site= omsite)
remote_url <- as.character(finfo$remote_url)
runid_base = 0
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

dat_all_flows <- sqldf(
  "select a.Qout as Qout, b.Qout as Qbaseline, 
     a.year, a.month, a.day 
   from dat as a
   left outer join dat_base as b
   on (a.year = b.year and a.month = b.month and a.day = b.day)
   order by a.year, a.month, a.day
  "
)
dat_all_flows$Date <- as.Date(
  paste(as.integer(dat_all_flows$year), as.integer(dat_all_flows$month), as.integer(dat_all_flows$day), 
      sep="/")
)
# not yet ready everywhere since all components do NOT have the ws and ps cumulative variables
# really, this should load the zero run, and compare a similar time period.
# If no run zero, we can do this:
if ( is.boolean(runid_base) ) {
  dat$Qbaseline <- dat$Qreach +
    (dat$wd_cumulative_mgd - dat$ps_cumulative_mgd ) * 1.547
}
#ifim_featureid = 397293 # Posey Hollow
ifim_featureid = 397294 # Rt 648
#ifim_featureid = 397294 # Rt 648
ifim_dataframe <- vahydro_prop_matrix(ifim_featureid, 'dh_feature','ifim_habitat_table')
WUA.df <- t(ifim_dataframe)
targets <- colnames(WUA.df)[-1]
ifim_da_sqmi = 737 # need to change to dynamic
curr_plot_100pct <- pothab_plot(
  WUA.df, dat_all_flows, "Qbaseline", "Qout",
  1.0, ifim_da_sqmi,
  "Posey Hollow", "Current"
)

curr_plot_50pct <- pothab_plot(
  WUA.df, dat_all_flows, "Qbaseline", "Qout",
  0.5, ifim_da_sqmi,
  "Posey Hollow", "Current"
)

curr_plot_25pct <- pothab_plot(
  WUA.df, dat_all_flows, "Qbaseline", "Qout",
  0.25, ifim_da_sqmi,
  "Posey Hollow", "Current"
)

curr_plot_10pct <- pothab_plot(
  WUA.df, dat_all_flows, "Qbaseline", "Qout",
  0.1, ifim_da_sqmi,
  "Posey Hollow", "Current"
)

curr_plot_5pct <- pothab_plot(
  WUA.df, dat_all_flows, "Qbaseline", "Qout",
  0.05, ifim_da_sqmi,
  "Posey Hollow", "Current"
)

print(1) # to act as positive returning function