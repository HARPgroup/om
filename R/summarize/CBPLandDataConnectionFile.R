# dirs/URLs
library(stringr)
save_directory <- "/var/www/html/files/fe/plots"
#----------------------------------------------
# Load Libraries
basepath='/var/www/R';
source(paste(basepath,'config.R',sep='/'))
save_directory <-  "/var/www/html/data/proj3/out"
suppressPackageStartupMessages(library(hydrotools))

# Now do the stuff
#pid = 4823212
#elid = 233571
#runid = 11
argst <- commandArgs(trailingOnly=T)
pid <- as.integer(argst[1])
elid <- as.integer(argst[2])
runid <- as.integer(argst[3])

# Post up a run summary for this runid
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
} else {
  # This is a re-run so blank out props first (if they exist)
  for (pname in c('l90_RUnit', 'l90_year', 'Runit')) {
    null_prop <- vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, pname, NULL, ds)
  }
  for (pname in c('Runit_boxplot_month', 'Runit_boxplot_year')) {
    null_prop <- vahydro_post_metric_to_scenprop(scenprop$pid, 'dh_image_file', NULL, pname, NULL, ds)
  }
}
warnings <- scenprop$set_prop('warnings', varkey='om_annotation')
if (!is.na(warnings$pid)) {
  warnings$delete_props(TRUE)
}

dat <- fn_get_runfile(elid, runid, site= omsite,  cached = FALSE);
# grab model run period before removing warmup period
model_run_start <- min(dat$thisdate) 
model_run_end <- max(dat$thisdate)
# eliminate warmup period
dat <- fn_remove_model_warmup(dat)
sdate <- min(dat$thisdate)
edate <- max(dat$thisdate)
dat$Runit <- as.numeric(dat$Qout) / as.numeric(dat$area_sqmi)
Runits <- zoo(as.numeric(as.character( dat$Runit )), order.by = as.POSIXct(dat$thisdate));


# POSTING METRICS TO SCENARIO PROPERTIES ON VA HYDRO
# QA
loflows <- group2(Runits, "calendar")
l90 <- loflows["90 Day Min"]
ndx = which.min(as.numeric(l90[,"90 Day Min"]))
l90_RUnit = round(loflows[ndx,]$"90 Day Min",6)
l90_year = loflows[ndx,]$"year"

if (is.na(l90_year)) {
  l90_Runit = 0.0
  l90_year = 0
}
l90prop <- vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'l90_RUnit', l90_RUnit, ds)
l90yr_prop <- vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'l90_year', l90_year, ds)
# post warnings if need be
if (l90_Runit == 0.0) {
  warning <- warnings$set_prop('l90_Runit', varkey='om_annotation', propcode = paste('l90_Runit is 0.0 in simulation',runid))
}
Runit <- mean(as.numeric(dat$Runit) )
if (is.na(Runit)) {
  Runit = 0.0
}
# post warnings if need be
Runitprop <- vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'Runit', Runit, ds)
if (Runit == 0.0) {
  warning <- warnings$set_prop('Runit', varkey='om_annotation', propcode = paste('Runit is 0.0 in simulation',runid))
}
# Runoff boxplot
fname <- paste0(
  'Runit_boxplot_year',
  elid, '.', runid, '.png'
)
fpath <-  paste(
  save_directory,
  fname,
  sep='/'
)
furl <- paste(
  save_url,
  fname,
  sep='/'
)
png(fpath)
boxplot(as.numeric(dat$Runit) ~ dat$year, ylim=c(0,3))
dev.off()
message(paste("Saved file: ", fname, "with URL", furl))
vahydro_post_metric_to_scenprop(scenprop$pid, 'dh_image_file', furl, 'Runit_boxplot_year', 0.0, ds)

# Runoff boxplot
fname <- paste0(
  'Runit_boxplot_month',
  elid, '.', runid, '.png'
)
fpath <-  paste(
  save_directory,
  fname,
  sep='/'
)
furl <- paste(
  save_url,
  fname,
  sep='/'
)
png(fpath)
boxplot(as.numeric(dat$Runit) ~ as.integer(dat$month), ylim=c(0,3))
dev.off()
message(paste("Saved file: ", fname, "with URL", furl))
vahydro_post_metric_to_scenprop(scenprop$pid, 'dh_image_file', furl, 'Runit_boxplot_month', 0.0, ds)


print(1) # to act as positive returning function