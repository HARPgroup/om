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


ifim_dataframe <- vahydro_prop_matrix(ifim_featureid, 'dh_feature','ifim_habitat_table')
WUA.df <- t(ifim_dataframe)
targets <- colnames(WUA.df)[-1]
