################################
#### *** Water Supply Element
################################
library(stringr)
library(ggplot2)
library(sqldf)
library(ggnewscale)
library(dplyr)

# dirs/URLs

#----------------------------------------------
site <- "http://deq2.bse.vt.edu/d.dh"    #Specify the site of interest, either d.bet OR d.dh
#----------------------------------------------
# Load Libraries
basepath='/var/www/R';
source(paste(basepath,'config.R',sep='/'))

save_directory <-  "/var/www/html/data/proj3/out"

# Read Args
# @todo: be able to detect if this is called via a R source call,
#        in which case it could be redundant to add these and retrieve data?
#       Alternatively call these as a function, passing in dat
argst <- commandArgs(trailingOnly=T)
pid <- as.integer(argst[1])
elid <- as.integer(argst[2])
runid <- as.integer(argst[3])
comp_name <- as.character(argst[4])

if (pid == '--help') {
  message("Usage: Rscript any_impoundment.R pid elid runid comp_name")
  quit()
}

dat <- fn_get_runfile(elid, runid, site= omsite,  cached = FALSE)
# grab model run period before removing warmup period
model_run_start <- min(dat$thisdate) 
model_run_end <- max(dat$thisdate)
# eliminate warmup period
dat <- fn_remove_model_warmup(dat)
sdate <- min(dat$thisdate)
edate <- max(dat$thisdate)
# yrdat will be used for generating the heatmap with calendar years
yrdat <- dat

yr_sdate <- as.Date(paste0((as.numeric(syear) + 1),"-01-01"))
yr_edate <- as.Date(paste0(eyear,"-12-31"))

yrdat <- window(yrdat, start = yr_sdate, end = yr_edate);

# water year data frame
mode(dat) <- 'numeric'
scen.propname<-paste0('runid_', runid)

# GETTING SCENARIO PROPERTY FROM VA HYDRO
sceninfo <- list(
  varkey = 'om_scenario',
  propname = scen.propname,
  featureid = pid,
  entity_type = "dh_properties"
)
scenprop <- getProperty(sceninfo, site, scenprop)
# POST PROPERTY IF IT IS NOT YET CREATED
if (identical(scenprop, FALSE)) {
  # create
  sceninfo$pid = NULL
} else {
  sceninfo$pid = scenprop$pid
}
scenprop = postProperty(inputs=sceninfo,base_url=base_url,prop)
scenprop <- getProperty(sceninfo, site, scenprop)

cols <- names(dat)
qvar <- paste(comp_name, 'Qin', sep='_')
remvar <- paste(comp_name, 'use_remain_mg', sep='_')
maxvar <- paste(comp_name, 'max_usable', sep='_')
demvar <- paste(comp_name, 'demand', sep='_')
outvar <- paste(comp_name, 'Qout', sep='_')
# @todo: refill amount is not reported by the imp object
#       this needs to change, but in the meantime we will look for 
#       a var named "[compname]_refill" or "ps_refill_pump_mgd"
plot_refill = FALSE
refvar = paste(comp_name, 'refill', sep='_')
if (refvar %in% cols) {
  plot_refill = TRUE
} else {
  if ("ps_refill_pump_mgd" %in% cols) {
    refvar = "ps_refill_pump_mgd" 
    plot_refill = TRUE
  }
}

# Metrics that need Zoo (IHA)
message(paste("Looking for", qvar, "to create zoo timeseries"))
flows <- zoo(as.numeric(as.character( dat[,qvar] )), order.by = index(dat));
message("Calculating Low Flows")
loflows <- group2(flows);
l90 <- loflows["90 Day Min"];
ndx = which.min(as.numeric(l90[,"90 Day Min"]));
l90_Qout = round(loflows[ndx,]$"90 Day Min",6);
l90_year = loflows[ndx,]$"year";

# Plot and analyze impoundment sub-comps
dat$storage_pct <- dat[,remvar] * 3.07 / dat[,maxvar]
#
storage_pct <- mean(as.numeric(dat$storage_pct) )
if (is.na(storage_pct)) {
  usable_pct_p0 <- 0
  usable_pct_p10 <- 0
  usable_pct_p50 <- 0
} else {
  usable_pcts = quantile(as.numeric(dat$storage_pct), c(0,0.1,0.5) )
  usable_pct_p0 <- usable_pcts["0%"]
  usable_pct_p10 <- usable_pcts["10%"]
  usable_pct_p50 <- usable_pcts["50%"]
}

# post em up
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'usable_pct_p0', usable_pct_p0, ds)
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'usable_pct_p10', usable_pct_p10, ds)
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'usable_pct_p50', usable_pct_p50, ds)


# this has an impoundment.  Plot it up.
# Now zoom in on critical drought period
pdstart = as.Date(paste0(l90_year,"-06-01") )
pdend = as.Date(paste0(l90_year, "-11-15") )
datpd <- window(
  dat,
  start = pdstart,
  end = pdend
);
fname <- paste(
  save_directory,
  paste0(
    'l90_imp_storage.',
    elid, '.', runid, '.png'
  ),
  sep = '/'
)
furl <- paste(
  save_url,
  paste0(
    'l90_imp_storage.',
    elid, '.', runid, '.png'
  ),
  sep = '/'
)
png(fname)
ymn <- 1
ymx <- 100
par(mar = c(5,5,2,5))
plot(
  datpd$storage_pct * 100.0,
  ylim=c(ymn,ymx),
  ylab="Reservoir Storage (%)",
  xlab=paste("Lowest 90 Day Flow Period",pdstart,"to",pdend)
)
par(new = TRUE)
ymx2 <- max(
  datpd[,demvar] * 1.547,
  datpd[,outvar],
  datpd$ps_refill_pump_mgd,
  datpd[,qvar]
)
plot(datpd[,qvar],col='blue', axes=FALSE, xlab="", ylab="",
     ylim=c(0,ymx2))
lines(datpd[,outvar],col='darkblue')
if (plot_refill) {
  lines(datpd[,refvar] * 1.547,col='green')
}
lines(datpd[,demvar] * 1.547,col='red')
axis(side = 4)
mtext(side = 4, line = 3, 'Flow/Demand (cfs)')
dev.off()
message(paste("Saved file: ", fname, "with URL", furl))
vahydro_post_metric_to_scenprop(scenprop$pid, 'dh_image_file', furl, 'fig.l90_imp_storage', 0.0, ds)

# l90 2 year
# this has an impoundment.  Plot it up.
# Now zoom in on critical drought period
pdstart = as.Date(paste0( (as.integer(l90_year) - 1),"-01-01") )
pdend = as.Date(paste0(l90_year, "-12-31") )
datpd <- window(
  dat,
  start = pdstart,
  end = pdend
);
fname <- paste(
  save_directory,
  paste0(
    'l90_imp_storage.2yr.',
    elid, '.', runid, '.png'
  ),
  sep = '/'
)
furl <- paste(
  save_url,
  paste0(
    'l90_imp_storage.2yr.',
    elid, '.', runid, '.png'
  ),
  sep = '/'
)
png(fname)
ymn <- 1
ymx <- 100
par(mar = c(5,5,2,5))
plot(
  datpd$storage_pct * 100.0,
  ylim=c(ymn,ymx),
  ylab="Reservoir Storage (%)",
  xlab=paste("Lowest 90 Day Flow Period",pdstart,"to",pdend)
)
par(new = TRUE)
plot(datpd[,qvar],col='blue', axes=FALSE, xlab="", ylab="")
lines(datpd[,outvar],col='green')
lines(datpd[,demvar] * 1.547,col='red')
axis(side = 4)
mtext(side = 4, line = 3, 'Flow/Demand (cfs)')
dev.off()
message(paste("Saved file: ", fname, "with URL", furl))
vahydro_post_metric_to_scenprop(scenprop$pid, 'dh_image_file', furl, 'fig.l90_imp_storage.2yr', 0.0, ds)

# All Periods
# this has an impoundment.  Plot it up.
# Now zoom in on critical drought period
datpd <- dat
fname <- paste(
  save_directory,
  paste0(
    'fig.imp_storage.all.',
    elid, '.', runid, '.png'
  ),
  sep = '/'
)
furl <- paste(
  save_url,
  paste0(
    'fig.imp_storage.all.',
    elid, '.', runid, '.png'
  ),
  sep = '/'
)
png(fname)
ymn <- 1
ymx <- 100
par(mar = c(5,5,2,5))
plot(
  datpd$storage_pct * 100.0,
  ylim=c(ymn,ymx),
  ylab="Reservoir Storage (%)",
  xlab=paste("Storage and Flows",sdate,"to",edate)
)
par(new = TRUE)
plot(datpd[,qvar],col='blue', axes=FALSE, xlab="", ylab="")
lines(datpd[,outvar],col='green')
lines(datpd[,demvar] * 1.547,col='red')
axis(side = 4)
mtext(side = 4, line = 3, 'Flow/Demand (cfs)')
dev.off()
message(paste("Saved file: ", fname, "with URL", furl))
vahydro_post_metric_to_scenprop(scenprop$pid, 'dh_image_file', furl, 'fig.imp_storage.all', 0.0, ds)

# Low Elevation Period
# Dat for Critical Period
elevs <- zoo(dat$storage_pct, order.by = index(dat));
loelevs <- group2(elevs);
l90 <- loelevs["90 Day Min"];
ndx = which.min(as.numeric(l90[,"90 Day Min"]));
l90_elev = round(loelevs[ndx,]$"90 Day Min",6);
l90_elevyear = loelevs[ndx,]$"year";
l90_elev_start = as.Date(paste0(l90_elevyear - 2,"-01-01"))
l90_elev_end = as.Date(paste0(l90_elevyear,"-12-31"))
elevdatpd <- window(
  dat,
  start = l90_elev_start,
  end = l90_elev_end
);
datpd <- elevdatpd
fname <- paste(
  save_directory,
  paste0(
    'elev90_imp_storage.all.',
    elid, '.', runid, '.png'
  ),
  sep = '/'
)
furl <- paste(
  save_url,
  paste0(
    'elev90_imp_storage.all.',
    elid, '.', runid, '.png'
  ),
  sep = '/'
)
png(fname)
ymn <- 1
ymx <- 100
par(mar = c(5,5,2,5))
plot(
  datpd$storage_pct * 100.0,
  ylim=c(ymn,ymx),
  main="Minimum Modeled Reservoir Storage Period",
  ylab="Reservoir Storage (%)",
  xlab=paste("Model Time Period",l90_elev_start,"to",l90_elev_end)
)
par(new = TRUE)
plot(datpd[,qvar],col='blue', axes=FALSE, xlab="", ylab="")
lines(datpd[,qvar],col='green')
lines(datpd[,demvar] * 1.547,col='red')
axis(side = 4)
mtext(side = 4, line = 3, 'Flow/Demand (cfs)')
dev.off()
message(paste("Saved file: ", fname, "with URL", furl))
vahydro_post_metric_to_scenprop(scenprop$pid, 'dh_image_file', furl, 'elev90_imp_storage.all', 0.0, ds)


print(1) # to act as positive returning function