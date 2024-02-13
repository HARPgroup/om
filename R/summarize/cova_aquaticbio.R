# Produce a habitat alteration plot
# NOte: model element must have custom1 = cova_aquaticbio
# things that can be stored on the model -> reports -> habitat property:
# - base_flow_column: (default: auto, will calc via wd_mgd/ps_mgd and Qout)
# - flow_column: (default Qout)
# - habitat_grid: wua (default name of habitat matrix prop)
# - target_cols: which columns to show: (default: all)
# 

#----------------------------------------------
site <- "http://deq1.bse.vt.edu/d.dh"    #Specify the site of interest, either d.bet OR d.dh
#----------------------------------------------
# Load Libraries
library(hydrotools)
basepath='/var/www/R';
source(paste(basepath,'config.R',sep='/'))
source(paste("https://raw.githubusercontent.com/HARPgroup/r-dh-ecohydro",'master/Analysis/habitat','ifim_wua_change_plot.R',sep='/'))
source(paste("https://raw.githubusercontent.com/HARPgroup/r-dh-ecohydro",'master/Analysis/habitat','hab_ts_functions.R',sep='/'))
# Load Libraries
basepath='/var/www/R';
source(paste(basepath,'config.R',sep='/'))
save_directory <-  "/var/www/html/data/proj3/out"
library(hydrotools)
# authenticate
ds <- RomDataSource$new(site, rest_uname)
ds$get_token(rest_pw)

# Read Args
argst <- commandArgs(trailingOnly=T)
pid <- as.integer(argst[1])
elid <- as.integer(argst[2])
runid <- as.integer(argst[3])

finfo <- fn_get_runfile_info(elid, runid,37, site= omsite)
remote_url <- as.character(finfo$remote_url)
dat <- fn_get_runfile(elid, runid, site= omsite,  cached = FALSE)
syear = min(dat$year)
eyear = max(dat$year)
if (syear != eyear) {
  sdate <- as.Date(paste0(syear,"-10-01"))
  edate <- as.Date(paste0(eyear,"-09-30"))
} else {
  # special case to handle 1 year model runs
  # just omit January in order to provide a short warmup period.
  sdate <- as.Date(paste0(syear,"-02-01"))
  edate <- as.Date(paste0(eyear,"-12-31"))
}
cols <- names(dat)

# not yet ready everywhere since all components do NOT have the ws and ps cumulative variables
# really, this should load the zero run, and compare a similar time period.
dat$Qbaseline <- dat$Qreach +
  (dat$wd_cumulative_mgd - dat$ps_cumulative_mgd ) * 1.547

model <- RomProperty(ds, list(pid=pid))
model_dms <- RomProperty(ds, list(featureid=model$pid, entity_type='dh_properties', varkey='om_class_DataMatrix'))

ifim_dataframe <- vahydro_prop_matrix(ifim_featureid, 'dh_feature','ifim_habitat_table')
WUA.df <- t(ifim_dataframe)
targets <- colnames(WUA.df)[-1]

wua_gf <- read.table(
  "https://raw.githubusercontent.com/HARPgroup/vahydro/master/R/permitting/potomac/potomac_lfaa/wua_gf.csv"
  , header=TRUE, sep=","
)
ifim_da_sqmi = 11010
ifim_site_name = "Great Falls"

curr_plot_gf100 <- pothab_plot(
  wua_gf, lf_alt_usgs, "flow_baseline", "flow_obs",
  1.0, ifim_da_sqmi,
  "Great Falls", "Current"
)

