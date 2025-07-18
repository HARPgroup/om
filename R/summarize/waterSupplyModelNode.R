#----------------------------------------------
site <- "http://deq1.bse.vt.edu/d.dh"    #Specify the site of interest, either d.bet OR d.dh
#----------------------------------------------
# Load Libraries
basepath='/var/www/R';
source(paste(basepath,'config.R',sep='/'))
#----------------------------------------------
#site <- base_url    #Specify the site of interest, either d.bet OR d.dh, taken from the config.R
# this is now set in config.local.R
#----------------------------------------------
source(paste(om_location,'R/summarize','rseg_elfgen.R',sep='/'))
library(stringr)
# dirs/URLs
save_directory <- "/var/www/html/data/proj3/out"
suppressPackageStartupMessages(library(hydrotools))
suppressPackageStartupMessages(library(IHA))
# authenticate

source('https://github.com/HARPgroup/om/raw/main/R/summarize/fn_get_pd_min.R')

# Read Args argst <- c(4713892, 231299 , 11)
argst <- commandArgs(trailingOnly=T)
pid <- as.integer(argst[1])
elid <- as.integer(argst[2])
runid <- as.integer(argst[3])

finfo <- fn_get_runfile_info(elementid = elid, runid = runid, site=omsite)
remote_url <- finfo$remote_url
# Note: when we migrate to om_get_rundata()
# we must insure that we do NOT use the auto-trim to water year
# as we want to have the model_run_start and _end for scenario storage
dat <- fn_get_runfile(elid, runid, site= omsite,  cached = FALSE)
# grab model run period before removing warmup period
model_run_start <- min(dat$thisdate) 
model_run_end <- max(dat$thisdate)
# eliminate warmup period
dat <- fn_remove_model_warmup(dat)
sdate <- min(dat$thisdate)
edate <- max(dat$thisdate)
syear <- year(sdate)
eyear <- year(edate)
flow_year_type <- 'calendar'
#dat <- as.zoo(dat)
mode(dat) <- 'numeric'
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
scenprop$startdate <- model_run_start
scenprop$enddate <- model_run_end
# save the date information
scenprop$save(TRUE)

keepers <- c('run_status', 'reports', 'logfile')
clear_scenario_data <- function(scenprop, keepers) {
  propnames <- scenprop$propvalues()
  if (is.logical(propnames)) {
    # no values
    return()
  }
  propnames = propnames[,'propname']
  for (i in propnames) {
    if (!(i %in% keepers)) {
      subprop <- scenprop$get_prop(i)
      if (!is.na(subprop$pid)) {
        subprop$delete(TRUE)
      }
    }
  }
}
clear_scenario_data(scenprop, keepers)

# Post link to run file
vahydro_post_metric_to_scenprop(scenprop$pid, 'external_file', remote_url, 'logfile', NA, ds)

# does this have an impoundment sub-comp and is imp_off = 0?
cols <- names(dat)
imp_off <- 1# default to no impouhd
if ("imp_off" %in% cols) {
  imp_off <- as.integer(median(dat$imp_off))
} else {
  if( (is.null(imp_off)) && ("impoundment" %in% cols) ) {
    # imp_off is NOT in the cols but impoundment IS
    # therefore, we assume that the impoundment is active by intention
    # and that it is a legacy that lacked the imp_off convention
    imp_off = 0
  } else {
    imp_off <- 1 # default to no impoundment
  }
}
message("**************************************")
message("**************************************")
message("**************************************")
message("**************************************")
message(paste("IMP_OFF = ", imp_off))
message("**************************************")
message("**************************************")
message("**************************************")
message("**************************************")
message("**************************************")

if (!("wd_mgd" %in% cols)) { 
  if ("child_wd_mgd" %in% cols) {
    dat$wd_mgd <- dat$child_wd_mgd
  } else {
    dat$wd_mgd <- 0.0
  }
}
wd_mgd <- mean(as.numeric(dat$wd_mgd) )
if (is.na(wd_mgd)) {
  wd_mgd = 0.0
}
if (("wd_imp_child_mgd" %in% cols)) { 
  wd_imp_child_mgd <- mean(as.numeric(dat$wd_imp_child_mgd) )
} else {
  wd_imp_child_mgd = 0.0
}
if (is.na(wd_imp_child_mgd)) {
  wd_imp_child_mgd = 0.0
}
# combine these two for reporting
wd_mgd <- wd_mgd + wd_imp_child_mgd

if ("wd_cumulative_mgd" %in% cols) { #cumulative variables are needed for calculations including Water Availability and combine all upstream contributions
  wd_cumulative_mgd <- mean(as.numeric(dat$wd_cumulative_mgd) )
  if (is.na(wd_cumulative_mgd)) {
    wd_cumulative_mgd = wd_mgd
  }
} else {
  wd_cumulative_mgd = wd_mgd
  dat$wd_cumulative_mgd <- dat$wd_mgd
}

if (!("ps_mgd" %in% cols)) { 
  if ("child_ps_mgd" %in% cols) {
    dat$ps_mgd <- dat$child_ps_mgd
  } else {
    dat$ps_mgd <- 0.0
  }
}
ps_mgd <- mean(as.numeric(dat$ps_mgd) )
if (is.na(ps_mgd)) {
  ps_mgd = 0.0
}
if ("ps_cumulative_mgd" %in% cols) { #cumulative variables are needed for calculations including Water Availability and combine all upstream contributions
  ps_cumulative_mgd <- mean(as.numeric(dat$ps_cumulative_mgd) )
  if (is.na(ps_cumulative_mgd)) {
    ps_cumulative_mgd = ps_mgd
  }
} else {
  ps_cumulative_mgd = ps_mgd
  dat$ps_cumulative_mgd <- dat$ps_mgd
}

ps_nextdown_mgd <- mean(as.numeric(dat$ps_nextdown_mgd) )
if (is.na(ps_nextdown_mgd)) {
  ps_nextdown_mgd = 0.0
}
Qout <- mean(as.numeric(dat$Qout) )
if (is.na(Qout)) {
  Qout = 0.0
}
if ("ps_refill_pump_mgd" %in% cols) { #cumulative variables are needed for calculations including Water Availability and combine all upstream contributions
  ps_refill_pump_mgd <- mean(as.numeric(dat$ps_refill_pump_mgd) )
  if (is.na(ps_refill_pump_mgd)) {
    ps_refill_pump_mgd = 0.0
  }
} else {
  ps_refill_pump_mgd = 0.0
}
net_consumption_mgd <- wd_cumulative_mgd - ps_cumulative_mgd
if (is.na(net_consumption_mgd)) {
  net_consumption_mgd = 0.0
}
dat$Qbaseline <- dat$Qout +
  (dat$wd_cumulative_mgd - dat$ps_cumulative_mgd ) * 1.547
# alter calculation to account for pump store
if (imp_off == 0) {
  if("impoundment_Qin" %in% cols) {
    if (!("ps_cumulative_mgd" %in% cols)) {
      dat$ps_cumulative_mgd <- 0.0
    }
    # a litle different than impoundment, since we want to evaluate refill storage
    # during short simulations this may appear to be consumption, but is temporary 
    # detention.  so we take Qin, then add back upstream withdrawals only
    dat$Qbaseline <- dat$impoundment_Qin +
      (dat$wd_cumulative_mgd - dat$wd_mgd - dat$ps_cumulative_mgd) * 1.547
  }
}

Qbaseline <- mean(as.numeric(dat$Qbaseline) )
if (is.na(Qbaseline)) {
  Qbaseline = Qout +
    (wd_cumulative_mgd - ps_cumulative_mgd ) * 1.547
}
# The total flow method of CU calculation
consumptive_use_frac <- 1.0 - (Qout / Qbaseline)
dat$consumptive_use_frac <- 1.0 - (dat$Qout / dat$Qbaseline)
# This method is more appropriate for impoundments that have long
# periods of zero outflow... but the math is not consistent with elfgen
daily_consumptive_use_frac <-  mean(as.numeric(dat$consumptive_use_frac) )
if (is.na(daily_consumptive_use_frac)) {
  daily_consumptive_use_frac <- 1.0 - (Qout / Qbaseline)
}
datdf <- as.data.frame(dat)
modat <- sqldf("select month, avg(wd_cumulative_mgd) as wd_mgd, avg(ps_cumulative_mgd) as ps_mgd from datdf group by month")
#barplot(wd_mgd ~ month, data=modat)

# post em up
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'net_consumption_mgd', net_consumption_mgd, ds)
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'wd_mgd', wd_mgd, ds)
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'wd_cumulative_mgd', wd_cumulative_mgd, ds)
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'ps_mgd', ps_mgd, ds)
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'ps_cumulative_mgd', ps_cumulative_mgd, ds)
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'Qout', Qout, ds)
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'Qbaseline', Qbaseline, ds)
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'ps_nextdown_mgd', ps_nextdown_mgd, ds)
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'consumptive_use_frac', consumptive_use_frac, ds)
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'daily_consumptive_use_frac', daily_consumptive_use_frac, ds)
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'ps_refill_pump_mgd', ps_refill_pump_mgd, ds)

# Metrics that need Zoo (IHA)
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
## Water year is overridden in group2() by calendar year for L30 and L90 calculations to avoid overestimation of Smin in the current year and underestimation in the next
## The period of minimum storage in impoundments (period of Smin) often overlaps with Oct 1st 
#loflows <- group2(flows, flow_year_type); 

# h1
iout <- fn_iha_flow_extreme(flows, "1 Day Max","max")
h1_Qout <- iout[1]
h1_year <- iout[2]
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'max1_Qout', h1_Qout, ds)
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'max1_year', h1_year, ds)
# h3
iout <- fn_iha_flow_extreme(flows, "3 Day Max", "max")
h3_Qout <- iout[1]
h3_year <- iout[2]
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'max3_Qout', h3_Qout, ds)
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'max3_year', h3_year, ds)

# l90
iout <- fn_iha_flow_extreme(flows, "90 Day Min")
l90_Qout <- iout[1]
l90_year <- iout[2]
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'l90_Qout', l90_Qout, ds)
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'l90_year', l90_year, ds)

iout <- fn_iha_flow_extreme(flows, "30 Day Min")
l30_Qout <- iout[1]
l30_year <- iout[2]
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'l30_Qout', l30_Qout, ds)
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'l30_year', l30_year, ds)
iout <- fn_iha_flow_extreme(flows, "7 Day Min")
l7_Qout <- iout[1]
l7_year <- iout[2]
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'l07_Qout', l7_Qout, ds)
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'l07_year', l7_year, ds)

# 7q10 -- also requires PearsonDS packages
x7q10 <- fn_iha_7q10(flows)

if (is.na(x7q10)) {
  x7q10 = 0.0
}
vahydro_post_metric_to_scenprop(scenprop$pid, '7q10', NULL, '7q10', x7q10, ds)

# ALF -- also requires IHA package and lubridate
alf_data <- data.frame(matrix(data = NA, nrow = length(dat$thisdate), ncol = 5))
colnames(alf_data) <- c('Qout', 'thisdate', 'year', 'month', 'day')
alf_data$Qout <- dat$Qout
alf_data$thisdate <- index(dat)
alf_data$year <- year(ymd(alf_data$thisdate))
alf_data$month <- month(ymd(alf_data$thisdate))
alf_data$day <- day(ymd(alf_data$thisdate))
zoo.alf_data <- zoo(alf_data$Qout, order.by = alf_data$thisdate)
alf <- fn_iha_mlf(zoo.alf_data,'August')

vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'ml8', alf, ds)

# Sept. 10%
sept_flows <- subset(alf_data, month == '9')
sept_10 <- as.numeric(round(quantile(sept_flows$Qout, 0.10),6))

vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'mne9_10', sept_10, ds)

#Unmet demand
unmet_demand_mgd <- mean(as.numeric(dat$unmet_demand_mgd) )
if (is.na(unmet_demand_mgd)) {
  unmet_demand_mgd = 0.0
}
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'unmet_demand_mgd', unmet_demand_mgd, ds)

# Metrics trimmed to climate change scenario timescale (Jan. 1 1990 -- Dec. 31 2000)
if (syear <= 1990 && eyear >= 2000) {
  sdate_trim <- as.POSIXct(paste0(1990,"-10-01"), tz='UTC')
  edate_trim <- as.POSIXct(paste0(2000,"-09-30"), tz='UTC')

  dat_trim <- window(dat, start = sdate_trim, end = edate_trim);
  # convert to daily
  dat_trim <- aggregate(
    dat_trim,
    as.POSIXct(
      format(
        time(dat_trim),
        format='%Y/%m/%d'),
      tz='UTC'
    ),
    'mean'
  )
  mode(dat_trim) <- 'numeric'

  flows_trim <- zoo(as.numeric(as.character( dat_trim$Qout )), order.by = index(dat_trim));
  loflows_trim <- group2(flows_trim);
  l90_trim <- loflows_trim["90 Day Min"];
  ndx_trim = which.min(as.numeric(l90_trim[,"90 Day Min"]));
  l90_Qout_trim = round(loflows_trim[ndx_trim,]$"90 Day Min",6);
  l90_year_trim = loflows_trim[ndx_trim,]$"year";

  if (is.na(l90_Qout_trim)) {
    l90_Qout_trim = 0.0
    l90_year_trim = 0
  }

  vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'l90_cc_Qout', l90_Qout_trim, ds)
  vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'l90_cc_year', l90_year_trim, ds)

  l30_trim <- loflows_trim["30 Day Min"];
  ndx_trim = which.min(as.numeric(l30_trim[,"30 Day Min"]));
  l30_Qout_trim = round(loflows_trim[ndx_trim,]$"30 Day Min",6);
  l30_year_trim = loflows_trim[ndx_trim,]$"year";

  if (is.na(l30_Qout_trim)) {
    l30_Qout_trim = 0.0
    l30_year_trim = 0
  }

  vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'l30_cc_Qout', l30_Qout_trim, ds)
  vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'l30_cc_year', l30_year_trim, ds)
}

message("Plotting critical flow periods")
# does this have an active impoundment sub-comp
if ( (imp_off == 0) & ("impoundment" %in% cols)) { #has impoundment 
  message("*******************************************")
  message("*******************************************")
  message("*******************************************")
  message("has IMPOUNDMENT")
  message("*******************************************")
  message("*******************************************")
  message("*******************************************")

  # Smin_CPL metrics
  start_date_30 <- paste0(l30_year,"-01-01") # Dates for l90_year
  end_date_30 <- paste0(l30_year,"-12-31")
  
  start_date_90 <- paste0(l90_year,"-01-01") # Dates for l30_year
  end_date_90 <- paste0(l90_year,"-12-31")
  Smin_L30_mg <- 0
  Smin_L90_mg <- 0
  # calculate Smin related 
  # Calculate Smin_CPLs using function
  Smin_L30_mg <- fn_get_pd_min(ts_data = dat, start_date = start_date_30, end_date = end_date_30,
                               colname = "impoundment_use_remain_mg")
 
  Smin_L90_mg <- fn_get_pd_min(ts_data = dat, start_date = start_date_90, end_date = end_date_90,
                               colname = "impoundment_use_remain_mg")
  
  vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'Smin_L30_mg', Smin_L30_mg, ds)
  vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'Smin_L90_mg', Smin_L90_mg, ds)
  


    # Plot and analyze impoundment sub-comps
    dat$storage_pct <- dat$impoundment_use_remain_mg * 3.07 / dat$impoundment_max_usable
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
    impoundment_days_remaining <- mean(as.numeric(dat$impoundment_days_remaining) )
    if (is.na(impoundment_days_remaining)) {
      remaining_days_p0 <- 0
      remaining_days_p10 <- 0
      remaining_days_p50 <- 0
    } else {
      remaining_days = quantile(as.numeric(dat$impoundment_days_remaining), c(0,0.1,0.5) )
      remaining_days_p0 <- remaining_days["0%"]
      remaining_days_p10 <- remaining_days["10%"]
      remaining_days_p50 <- remaining_days["50%"]
    }

    # post em up
    vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'usable_pct_p0', usable_pct_p0, ds)
    vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'usable_pct_p10', usable_pct_p10, ds)
    vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'usable_pct_p50', usable_pct_p50, ds)

    vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'remaining_days_p0', remaining_days_p0, ds)
    vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'remaining_days_p10', remaining_days_p10, ds)
    vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'remaining_days_p50', remaining_days_p50, ds)


    # this has an impoundment.  Plot it up.
    # Now zoom in on critical drought period
    pdstart = as.POSIXct(paste0(l90_year,"-06-01"), tz='UTC' )
    pdend = as.POSIXct(paste0(l90_year, "-11-15"), tz='UTC' )
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
    par(mar = c(8.8,5,0.5,5))
    plot(
      datpd$storage_pct * 100.0,
      ylim=c(ymn,ymx),
      ylab="Reservoir Storage (%)",
      xlab=paste("Lowest 90 Day Flow Period",pdstart,"to",pdend),
      legend=c('Storage', 'Qin', 'Qout', 'Demand (mgd)')
    )
    par(new = TRUE)
    plot(datpd$impoundment_Qin,col='blue', axes=FALSE, xlab="", ylab="")
    lines(datpd$impoundment_Qout,col='green')
    lines(datpd$impoundment_demand * 1.547,col='red')
    axis(side = 4)
    mtext(side = 4, line = 3, 'Flow/Demand (cfs)')
    legend("bottom",inset=-0.36, xpd=TRUE, c("Reservoir Storage","Inflow","Outflow","Demand"),
           col = c("black", "blue", "green","red"),
           lty = c(1,1,1,1),
           bg='white',cex=0.8) #ADD LEGEND
    dev.off()
    message(paste("Saved file: ", fname, "with URL", furl))
    vahydro_post_metric_to_scenprop(scenprop$pid, 'dh_image_file', furl, 'fig.l90_imp_storage', 0.0, ds)

    # l90 2 year
    # this has an impoundment.  Plot it up.
    # Now zoom in on critical drought period
    pdstart = as.POSIXct(paste0( (as.integer(l90_year) - 1),"-01-01"), tz='UTC' )
    pdend = as.POSIXct(paste0(l90_year, "-12-31"), tz='UTC' )
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
    par(mar = c(8.8,5,0.5,5))
    plot(
      datpd$storage_pct * 100.0,
      ylim=c(ymn,ymx),
      ylab="Reservoir Storage (%)",
      xlab=paste("Lowest 90 Day Flow Period",pdstart,"to",pdend)
    )
    par(new = TRUE)
    plot(datpd$impoundment_Qin,col='blue', axes=FALSE, xlab="", ylab="")
    lines(datpd$impoundment_Qout,col='green')
    lines(datpd$wd_mgd * 1.547,col='red')
    axis(side = 4)
    mtext(side = 4, line = 3, 'Flow/Demand (cfs)')
    legend("bottom",inset=-0.36, xpd=TRUE, c("Reservoir Storage","Inflow","Outflow","Demand"),
           col = c("black", "blue", "green","red"),
           lty = c(1,1,1,1),
           bg='white',cex=0.8) #ADD LEGEND
    dev.off()
    message(paste("Saved file: ", fname, "with URL", furl))
    vahydro_post_metric_to_scenprop(scenprop$pid, 'dh_image_file', furl, 'fig.l90_imp_storage.2yr', 0.0, ds)

    # All Periods
    # this has an impoundment.  Plot it up.

    # Full period Flow duration curve
    datpd <- dat
    fname <- paste(
      save_directory,
      paste0(
        'fig.fdc.all.',
        elid, '.', runid, '.png'
      ),
      sep = '/'
    )
    furl <- paste(
      save_url,
      paste0(
        'fig.fdc.all.',
        elid, '.', runid, '.png'
      ),
      sep = '/'
    )
    png(fname)
    hydroTSM::fdc(cbind(datpd$impoundment_Qin, datpd$impoundment_Qout),ylab="Q (cfs)")
    dev.off()
    message(paste("Saved file: ", fname, "with URL", furl))
    vahydro_post_metric_to_scenprop(scenprop$pid, 'dh_image_file', furl, 'fig.fdc.all.', 0.0, ds)


    # Full period inflow/outflow, res level
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
    par(mar = c(8.8,5,0.5,5))
    plot(
      datpd$storage_pct * 100.0,
      ylim=c(ymn,ymx),
      ylab="Reservoir Storage (%)",
      xlab=paste("Storage and Flows",sdate,"to",edate)
    )
    par(new = TRUE)
    plot(datpd$impoundment_Qin,col='blue', axes=FALSE, xlab="", ylab="")
    lines(datpd$impoundment_Qout,col='green')
    lines(datpd$wd_mgd * 1.547,col='red')
    axis(side = 4)
    mtext(side = 4, line = 3, 'Flow/Demand (cfs)')
    legend("bottom",inset=-0.36, xpd=TRUE, c("Reservoir Storage","Inflow","Outflow","Demand"),
           col = c("black", "blue", "green","red"),
           lty = c(1,1,1,1),
           bg='white',cex=0.8) #ADD LEGEND
    dev.off()
    message(paste("Saved file: ", fname, "with URL", furl))
    vahydro_post_metric_to_scenprop(scenprop$pid, 'dh_image_file', furl, 'fig.imp_storage.all', 0.0, ds)

    # Low Elevation Period
    # Dat for Critical Period
    elevs <- zoo(dat$storage_pct, order.by = index(dat));
    loelevs <- group2(elevs, flow_year_type);
    l90 <- loelevs["90 Day Min"];
    ndx = which.min(as.numeric(l90[,"90 Day Min"]));
    l90_elev = round(loelevs[ndx,]$"90 Day Min",6);
    l90_elevyear = loelevs[ndx,]$"year";
    l90_elev_start = as.POSIXct(paste0(l90_elevyear - 2,"-01-01"), tz='UTC')
    l90_elev_end = as.POSIXct(paste0(l90_elevyear,"-12-31"), tz='UTC')
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
    par(mar = c(8.8,5,01,5))
    plot(
      datpd$storage_pct * 100.0,cex.main=1,
      ylim=c(ymn,ymx),
      main="Minimum Modeled Reservoir Storage Period",
      ylab="Reservoir Storage (%)",
      xlab=paste("Model Time Period",l90_elev_start,"to",l90_elev_end)
    )
    par(new = TRUE)
    plot(datpd$impoundment_Qin,col='blue', axes=FALSE, xlab="", ylab="")
    lines(datpd$Qout,col='green')
    lines(datpd$wd_mgd * 1.547,col='red')
    axis(side = 4)
    mtext(side = 4, line = 3, 'Flow/Demand (cfs)')
    legend("bottom",inset=-0.36, xpd=TRUE, c("Reservoir Storage","Inflow","Outflow","Demand"),
           col = c("black", "blue", "green","red"),
           lty = c(1,1,1,1),
           bg='white',cex=0.8) #ADD LEGEND
  dev.off()
  message(paste("Saved file: ", fname, "with URL", furl))
  vahydro_post_metric_to_scenprop(scenprop$pid, 'dh_image_file', furl, 'elev90_imp_storage.all', 0.0, ds)

} else { #no impoundment 
  # plot Qin, Qout of mainstem, and wd_mgd, and wd_cumulative_mgd
  # TBD
  # l90 2 year
  message("**************************************")
  message("**************************************")
  message("**************************************")
  message("**************************************")
  message("Riverine model only, plotting regular hydrographs.")
  message("**************************************")
  message("**************************************")
  message("**************************************")
  message("**************************************")
  
  # No storage if there is no impoundment
  Smin_L30_mg <- 0
  Smin_L90_mg <- 0
  
  # Plot hydrographs only (no impoundment)
  # Now zoom in on critical drought period
  pdstart = as.POSIXct(paste0(l90_year,"-06-01"), tz='UTC' )
  pdend = as.POSIXct(paste0(l90_year, "-11-15"), tz='UTC' )
  datpd <- window(
    dat,
    start = pdstart,
    end = pdend
  );
  datpd <- as.zoo(datpd)
  fname <- paste(
    save_directory,
    paste0(
      'l90_flows.2yr.',
      elid, '.', runid, '.png'
    ),
    sep = '/'
  )
  furl <- paste(
    save_url,
    paste0(
      'l90_flows.2yr.',
      elid, '.', runid, '.png'
    ),
    sep = '/'
  )
  png(fname)
  # Because these are zoo timeseries, they will throw an error if you use a normal DF
  # max() syntax which is OK with max(c(df1, df2))
  # instead, we cbind them instead of the default which is an implicit rbind
  # ymx <- max(datpd$Qbaseline, datpd$Qout)
  ymx <- max(cbind(datpd$Qbaseline, datpd$Qout))
  plot(
    datpd$Qbaseline, ylim = c(0,ymx),
    ylab="Flow/WD/PS (cfs)",
    xlab=paste("Lowest 90 Day Flow Period",pdstart,"to",pdend)
  )
  lines(datpd$Qout,col='blue')
  par(new = TRUE)
  # Because these are zoo timeseries, they will throw an error if you use a normal DF
  # max() syntax which is OK with max(c(df1, df2))
  # instead, we cbind them instead of the default which is an implicit rbind
  # ymx <- max(cbind(datpd$wd_cumulative_mgd * 1.547, datpd$ps_cumulative_mgd * 1.547))
  ymx <- max(cbind(datpd$wd_cumulative_mgd * 1.547, datpd$ps_cumulative_mgd * 1.547))
  plot(
    datpd$wd_cumulative_mgd * 1.547,col='red',
    axes=FALSE, xlab="", ylab="", ylim=c(0,ymx)
  )
  lines(datpd$ps_cumulative_mgd * 1.547,col='green')
  axis(side = 4)
  mtext(side = 4, line = 3, 'Flow/Demand (cfs)')
  dev.off()
  message(paste("Saved file: ", fname, "with URL", furl))
  vahydro_post_metric_to_scenprop(scenprop$pid, 'dh_image_file', furl, 'fig.l90_flows.2yr', 0.0, ds)

  message("**************************************")
  message("**************************************")
  message("**************************************")
  message("**************************************")
  message("**************************************")
  message("PLOTTING FULL PERIOD FLOWS")
  message("**************************************")
  message("**************************************")
  message("**************************************")
  message("**************************************")

  datpd <- dat
  fname <- paste(
    save_directory,
    paste0(
      'flows.all.',
      elid, '.', runid, '.png'
    ),
    sep = '/'
  )
  furl <- paste(
    save_url,
    paste0(
      'flows.all.',
      elid, '.', runid, '.png'
    ),
    sep = '/'
  )
  png(fname)
  ymx <- max(cbind(max(datpd$Qbaseline), max(datpd$Qout)))
  plot(
    datpd$Qbaseline, ylim = c(0,ymx),
    ylab="Flow/WD/PS (cfs)",
    xlab=paste("Model Flow Period",sdate,"to",edate)
  )
  lines(datpd$Qout,col='blue')
  par(new = TRUE)
  ymx <- max(cbind(datpd$wd_cumulative_mgd * 1.547, datpd$ps_cumulative_mgd * 1.547))
  if (ymx == 0) {
    # set arbitrary to allow plotting
    ymx <- 1
  }
  plot(
    datpd$wd_cumulative_mgd * 1.547,col='red',
    axes=FALSE, xlab="", ylab="", ylim=c(0,ymx)
  )
  lines(datpd$ps_cumulative_mgd * 1.547,col='green')
  axis(side = 4)
  mtext(side = 4, line = 3, 'Flow/Demand (cfs)')
  dev.off()
  message("**************************************")
  message("**************************************")
  message("**************************************")
  message("**************************************")
  message("**************************************")
  message(paste("Saved file: ", fname, "with URL", furl))
  message("**************************************")
  message("**************************************")
  message("**************************************")
  message("**************************************")
  vahydro_post_metric_to_scenprop(scenprop$pid, 'dh_image_file', furl, 'fig.flows.all', 0.0, ds)

}

# Export Smin
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'Smin_L30_mg', Smin_L30_mg, ds)
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'Smin_L90_mg', Smin_L90_mg, ds)


###############################################
# RSEG FDC
###############################################
base_var <- "Qbaseline" #BASE VARIABLE USED IN FDCs AND HYDROGRAPHS
comp_var <- "Qout" #VARIABLE TO COMPARE AGAINST BASE VARIABLE, DEFAULT Qout

# FOR TESTING 
# save_directory <- 'C:/Users/nrf46657/Desktop/GitHub/om/R/summarize'
datpd <- datdf
fname <- paste(
  save_directory,
  paste0(
    'fdc.',
    elid, '.', runid, '.png'
  ),
  sep = '/'
)
# FOR TESTING 
# save_url <- save_directory
furl <- paste(
  save_url,
  paste0(
    'fdc.',
    elid, '.', runid, '.png'
  ),
  sep = '/'
)

#FDC fails when plotting neg values, so replace neg Qbaseline w/ 0 
if (any(datpd[,base_var] < 0)) { #check if any Qbaseline < 0
  datpd_pos <- datpd
  datpd_pos[,base_var] <- pmax(datpd_pos[,base_var], 0)
  subtitle <- '*Unreliable FDC caused by Baseline Flow < 0'
} else { 
  datpd_pos <- datpd
  subtitle <- ''
}

png(fname, width = 700, height = 700)
legend_text = c("Baseline Flow","Scenario Flow")
ymn <- 0
ymx <- max(cbind(as.numeric(unlist(datpd[names(datpd)== base_var])),
                 as.numeric(unlist(datpd[names(datpd)== comp_var]))))
if (ymx == 0) {
  # set arbitrary to allow plotting
  ymx <- 1
}

fdc_plot <- hydroTSM::fdc(
  cbind(datpd_pos[names(datpd_pos)== base_var], datpd_pos[names(datpd_pos)== comp_var]),
  # yat = c(0.10,1,5,10,25,100,400),
  # yat = c(round(min(datpd),0),500,1000,5000,10000),
  # yat = seq(round(min(datpd),0),round(max(datpd),0), by = 500),
  yat = c(1, 5, 10, 50, 100, seq(0,round(ymx,0), by = 500)),
  leg.txt = legend_text,
  main=paste("Flow Duration Curve","\n","(Model Flow Period ",sdate," to ",edate,")",sep=""),
  ylab = "Flow (cfs)",
  # ylim=c(1.0, 5000),
  # ylim=c(min(datpd), max(datpd)),
  ylim=c(ymn, ymx),
  cex.main=1.75,
  cex.axis=1.50,
  leg.cex=2,
  cex.sub = 1.2
)
title(sub = subtitle, adj = 0.85, line = 0.8)
dev.off()

message(paste("Saved file: ", fname, "with URL", furl))
vahydro_post_metric_to_scenprop(scenprop$pid, 'dh_image_file', furl, 'fig.fdc', 0.0, ds)


###############################################
###############################################


###############################################
# RSEG Hydrograph (Drought Period)
###############################################
# Zoom in on critical drought period
pdstart = as.POSIXct(paste0(l90_year,"-06-01"), tz='UTC' )
pdend = as.POSIXct(paste0(l90_year, "-11-15"), tz='UTC' )
datpd <- window(
  dat,
  start = pdstart,
  end = pdend
);
datpd <- data.frame(datpd)
datpd$date <- rownames(datpd)

fname <- paste(
  save_directory,
  paste0(
    'hydrograph_dry.',
    elid, '.', runid, '.png'
  ),
  sep = '/'
)
furl <- paste(
  save_url,
  paste0(
    'hydrograph_dry.',
    elid, '.', runid, '.png'
  ),
  sep = '/'
)

png(fname, width = 900, height = 700)
legend_text = c("Baseline Flow","Scenario Flow")
xmn <- as.POSIXct(pdstart, tz='UTC')
xmx <- as.POSIXct(pdend, tz='UTC')
ymn <- 0
#ymx <- 1000
ymx <- max(cbind(as.numeric(unlist(datpd[names(datpd)== base_var])),
                            as.numeric(unlist(datpd[names(datpd)== comp_var]))))
if (ymx == 0) {
  # set arbitrary to allow plotting
  ymx <- 1
}
par(mar = c(5,5,2,5))
hydrograph_dry <- plot(as.numeric(unlist(datpd[names(datpd)== base_var]))~as.POSIXct(datpd$date, tz='UTC'),
                       type = "l", lty=2, lwd = 1,ylim=c(ymn,ymx),xlim=c(xmn,xmx),
                       ylab="Flow (cfs)",xlab=paste("Lowest 90 Day Flow Period",pdstart,"to",pdend),
                       main = "Hydrograph: Dry Period",
                       cex.main=1.75,
                       cex.axis=1.50,
                       cex.lab=1.50
                       )
par(new = TRUE)
plot(as.numeric(unlist(datpd[names(datpd)== comp_var]))~as.POSIXct(datpd$date, tz='UTC'),
     type = "l",col='brown3', lwd = 2, 
     axes=FALSE,ylim=c(ymn,ymx),xlim=c(xmn,xmx),ylab="",xlab="")
legend("topright",legend=legend_text,col=c("black","brown3"), 
       lty=c(2,1), lwd=c(1,2), cex=1.5)
dev.off()

message(paste("Saved file: ", fname, "with URL", furl))
vahydro_post_metric_to_scenprop(scenprop$pid, 'dh_image_file', furl, 'fig.hydrograph_dry', 0.0, ds)
###############################################
###############################################


###############################################
# RSEG ELFGEN
###############################################
#GET RSEG HYDROID FROM RSEG MODEL PID
#rseg <-getProperty(list(pid=pid), site)
rseg <- RomProperty$new( ds, list(pid=pid), TRUE)
rseg_hydroid<-rseg$featureid

huc_level <- 'huc8'
dataset <- 'VAHydro-EDAS'

elfgen_huc(runid, rseg_hydroid, huc_level, dataset, scenprop, ds, save_directory, save_url, site)
###############################################
###############################################



print(1) # to act as positive returning function
