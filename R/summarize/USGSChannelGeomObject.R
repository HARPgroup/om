# dirs/URLs
save_directory <- "/var/www/html/files/fe/plots"
#----------------------------------------------
# Load Libraries
basepath='/var/www/R';
source(paste(basepath,'config.R',sep='/'))
suppressPackageStartupMessages(library(hydrotools))

# Camp Creek - 279191
argst <- commandArgs(trailingOnly=T)
pid <- as.integer(argst[1])
elid <- as.integer(argst[2])
runid <- as.integer(argst[3])

dat <- fn_get_runfile(elid, runid, site= omsite,  cached = FALSE)
# grab model run period before removing warmup period
model_run_start <- min(dat$thisdate) 
model_run_end <- max(dat$thisdate)
# eliminate warmup period
dat <- fn_remove_model_warmup(dat)
sdate <- min(dat$thisdate)
edate <- max(dat$thisdate)
flow_year_type <- 'calendar'
amn <- 10.0 * mean(as.numeric(dat$Qout))

scen.propname<-paste0('runid_', runid)

# GETTING SCENARIO PROPERTY FROM VA HYDRO
sceninfo <- list(
  varkey = 'om_scenario',
  propname = scen.propname,
  featureid = pid,
  entity_type = "dh_properties",
  bundle = "dh_properties"
)
scenprop <- RomProperty$new( ds, sceninfo, TRUE)

# POST PROPERTY IF IT IS NOT YET CREATED
if (is.na(scenprop$pid) | is.null(scenprop$pid) ) {
  # create
  scenprop$save(TRUE)
}

Rmean <- mean(as.numeric(dat$Runit) )
if (is.na(Rmean)) {
  Rmean = 0.0
}
Rmeanprop <- vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'Rmean', Rmean, ds)

Qin_mean <- mean(as.numeric(dat$Qin) )
if (is.na(Rmean)) {
  Rmean = 0.0
}
Qin_meanprop <- vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'Qin_mean', Qin_mean, ds)

Qout_mean <- mean(as.numeric(dat$Qout) )
if (is.na(Rmean)) {
  Rmean = 0.0
}
Qout_meanprop <- vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'Qout_mean', Qout_mean, ds)

flows <- zoo(as.numeric(as.character( dat$Qout )), order.by = index(dat));
# convert to daily
flows <- aggregate(
  flows,
  as.POSIXct(
    format(
      time(flows),
      format='%Y/%m/%d'),
    tz='UTC'
  ),
  'mean'
)


# 7q10 -- also requires PearsonDS packages
x7q10 <- fn_iha_7q10(flows)

if (is.na(x7q10)) {
  x7q10 = 0.0
}
vahydro_post_metric_to_scenprop(scenprop$pid, '7q10', NULL, '7q10', x7q10, ds)

loflows <- group2(flows, flow_year_type);
l90 <- loflows["90 Day Min"];
ndx = which.min(as.numeric(l90[,"90 Day Min"]));
l90_Qout = round(loflows[ndx,]$"90 Day Min",6);
l90_year = loflows[ndx,]$"year";

if (is.na(l90_Qout)) {
  l90_Qout = 0.0
  l90_year = 0
}

vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'l90_Qout', l90_Qout, ds)
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'l90_year', l90_year, ds)

l30 <- loflows["30 Day Min"];
ndx = which.min(as.numeric(l30[,"30 Day Min"]));
l30_Qout = round(loflows[ndx,]$"30 Day Min",6);
l30_year = loflows[ndx,]$"year";

if (is.na(l30_Qout)) {
  l30_Qout = 0.0
  l30_year = 0
}

vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'l30_Qout', l30_Qout, ds)
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'l30_year', l30_year, ds)


print(1) # to act as positive returning function