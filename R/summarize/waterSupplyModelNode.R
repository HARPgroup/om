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

# Read Args
argst <- commandArgs(trailingOnly=T)
pid <- as.integer(argst[1])
elid <- as.integer(argst[2])
runid <- as.integer(argst[3])

finfo <- fn_get_runfile_info(elid, runid)
remote_url <- finfo$remote_url
dat <- fn_get_runfile(elid, runid, site= omsite,  cached = FALSE)
mode(dat) <- 'numeric'

# Hourly to Daily flow timeseries
#dat = aggregate(
#  dat,
#  as.POSIXct(
#    format(
#      time(dat), 
#      format='%Y/%m/%d'),
#    tz='UTC'
#  ),
#  'mean'
#)
syear = as.integer(min(dat$year))
eyear = as.integer(max(dat$year))
if (syear < (eyear - 2)) {
  sdate <- as.Date(paste0(syear,"-10-01"))
  edate <- as.Date(paste0(eyear,"-09-30"))
  flow_year_type <- 'water'
} else {
  sdate <- as.Date(paste0(syear,"-02-01"))
  edate <- as.Date(paste0(eyear,"-12-31"))
  flow_year_type <- 'calendar'
}
dat <- window(dat, start = sdate, end = edate);
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
sceninfo <- list(
  varkey = 'om_scenario',
  propname = scen.propname,
  featureid = pid,
  entity_type = "dh_properties"
)
# Post link to run file
vahydro_post_metric_to_scenprop(scenprop$pid, 'external_file', remote_url, 'logfile', NULL, site, token)


# does this have an impoundment sub-comp and is imp_off = 0?
cols <- names(dat)
imp_off <- NULL# default to no impouhd
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
wd_mgd <- mean(as.numeric(dat$wd_mgd) )
if (is.na(wd_mgd)) {
  wd_mgd = 0.0
}
wd_cumulative_mgd <- mean(as.numeric(dat$wd_cumulative_mgd) )
if (is.na(wd_cumulative_mgd)) {
  wd_cumulative_mgd = 0.0
}
ps_mgd <- mean(as.numeric(dat$ps_mgd) )
if (is.na(ps_mgd)) {
  ps_mgd = 0.0
}
ps_cumulative_mgd <- mean(as.numeric(dat$ps_cumulative_mgd) )
if (is.na(ps_cumulative_mgd)) {
  ps_cumulative_mgd = 0.0
}
ps_nextdown_mgd <- mean(as.numeric(dat$ps_nextdown_mgd) )
if (is.na(ps_nextdown_mgd)) {
  ps_nextdown_mgd = 0.0
}
Qout <- mean(as.numeric(dat$Qout) )
if (is.na(Qout)) {
  Qout = 0.0
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
    dat$Qbaseline <- dat$impoundment_Qin + 
      (dat$wd_cumulative_mgd - dat$ps_cumulative_mgd) * 1.547
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
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'net_consumption_mgd', net_consumption_mgd, site, token)
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'wd_mgd', wd_mgd, site, token)
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'wd_cumulative_mgd', wd_cumulative_mgd, site, token)
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'ps_mgd', ps_mgd, site, token)
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'ps_cumulative_mgd', ps_cumulative_mgd, site, token)
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'Qout', Qout, site, token)
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'Qbaseline', Qbaseline, site, token)
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'ps_nextdown_mgd', ps_nextdown_mgd, site, token)
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'consumptive_use_frac', consumptive_use_frac, site, token)
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'daily_consumptive_use_frac', daily_consumptive_use_frac, site, token)

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
loflows <- group2(flows, flow_year_type);
l90 <- loflows["90 Day Min"];
ndx = which.min(as.numeric(l90[,"90 Day Min"]));
l90_Qout = round(loflows[ndx,]$"90 Day Min",6);
l90_year = loflows[ndx,]$"year";

if (is.na(l90)) {
  l90_Runit = 0.0
  l90_year = 0
}

vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'l90_Qout', l90_Qout, site, token)
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'l90_year', l90_year, site, token)

l30 <- loflows["30 Day Min"];
ndx = which.min(as.numeric(l30[,"30 Day Min"]));
l30_Qout = round(loflows[ndx,]$"30 Day Min",6);
l30_year = loflows[ndx,]$"year";

if (is.na(l30)) {
  l30_Runit = 0.0
  l30_year = 0
}

vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'l30_Qout', l30_Qout, site, token)
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'l30_year', l30_year, site, token)

# 7q10 -- also requires PearsonDS packages
x7q10 <- fn_iha_7q10(flows)

if (is.na(x7q10)) {
  x7q10 = 0.0
}
vahydro_post_metric_to_scenprop(scenprop$pid, '7q10', NULL, '7q10', x7q10, site, token)

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

vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'ml8', alf, site, token)

# Sept. 10%
sept_flows <- subset(alf_data, month == '9')
sept_10 <- as.numeric(round(quantile(sept_flows$Qout, 0.10),6))

vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'mne9_10', sept_10, site, token)

#Unmet demand
unmet_demand_mgd <- mean(as.numeric(dat$unmet_demand_mgd) )
if (is.na(unmet_demand_mgd)) {
  unmet_demand_mgd = 0.0
}
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'unmet_demand_mgd', unmet_demand_mgd, site, token)

# Metrics trimmed to climate change scenario timescale (Jan. 1 1990 -- Dec. 31 2000)
if (syear <= 1990 && eyear >= 2000) {
  sdate_trim <- as.Date(paste0(1990,"-10-01"))
  edate_trim <- as.Date(paste0(2000,"-09-30"))
  
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
  
  if (is.na(l90_trim)) {
    l90_Qout_trim = 0.0
    l90_year_trim = 0
  }
  
  vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'l90_cc_Qout', l90_Qout_trim, site, token)
  vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'l90_cc_year', l90_year_trim, site, token)
  
  l30_trim <- loflows_trim["30 Day Min"];
  ndx_trim = which.min(as.numeric(l30_trim[,"30 Day Min"]));
  l30_Qout_trim = round(loflows_trim[ndx_trim,]$"30 Day Min",6);
  l30_year_trim = loflows_trim[ndx_trim,]$"year";
  
  if (is.na(l30_trim)) {
    l30_Qout_trim = 0.0
    l30_year_trim = 0
  }
  
  vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'l30_cc_Qout', l30_Qout_trim, site, token)
  vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'l30_cc_year', l30_year_trim, site, token)
}

message("Plotting critical flow periods")
# does this have an active impoundment sub-comp
if (imp_off == 0) {
  if("impoundment" %in% cols) {
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
    vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'usable_pct_p0', usable_pct_p0, site, token)
    vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'usable_pct_p10', usable_pct_p10, site, token)
    vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'usable_pct_p50', usable_pct_p50, site, token)
    
    vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'remaining_days_p0', remaining_days_p0, site, token)
    vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'remaining_days_p10', remaining_days_p10, site, token)
    vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'remaining_days_p50', remaining_days_p50, site, token)
    
    
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
    print(paste("Saved file: ", fname, "with URL", furl))
    vahydro_post_metric_to_scenprop(scenprop$pid, 'dh_image_file', furl, 'fig.l90_imp_storage', 0.0, site, token)
    
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
    print(paste("Saved file: ", fname, "with URL", furl))
    vahydro_post_metric_to_scenprop(scenprop$pid, 'dh_image_file', furl, 'fig.l90_imp_storage.2yr', 0.0, site, token)
    
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
    print(paste("Saved file: ", fname, "with URL", furl))
    vahydro_post_metric_to_scenprop(scenprop$pid, 'dh_image_file', furl, 'fig.imp_storage.all', 0.0, site, token)
    
    # Low Elevation Period
    # Dat for Critical Period
    elevs <- zoo(dat$storage_pct, order.by = index(dat));
    loelevs <- group2(elevs, flow_year_type);
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
    print(paste("Saved file: ", fname, "with URL", furl))
    vahydro_post_metric_to_scenprop(scenprop$pid, 'dh_image_file', furl, 'elev90_imp_storage.all', 0.0, site, token)
    
  }
} else {
  # plot Qin, Qout of mainstem, and wd_mgd, and wd_cumulative_mgd
  # TBD
  # l90 2 year
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
  ymx <- max(datpd$Qbaseline, datpd$Qout)
  plot(
    datpd$Qbaseline, ylim = c(0,ymx),
    ylab="Flow/WD/PS (cfs)",
    xlab=paste("Lowest 90 Day Flow Period",pdstart,"to",pdend)
  )
  lines(datpd$Qout,col='blue')
  par(new = TRUE)
  ymx <- max(datpd$wd_cumulative_mgd * 1.547, datpd$ps_cumulative_mgd * 1.547)
  plot(
    datpd$wd_cumulative_mgd * 1.547,col='red', 
    axes=FALSE, xlab="", ylab="", ylim=c(0,ymx)
  )
  lines(datpd$ps_cumulative_mgd * 1.547,col='green')
  axis(side = 4)
  mtext(side = 4, line = 3, 'Flow/Demand (cfs)')
  dev.off()
  print(paste("Saved file: ", fname, "with URL", furl))
  vahydro_post_metric_to_scenprop(scenprop$pid, 'dh_image_file', furl, 'fig.l90_flows.2yr', 0.0, site, token)
  
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
  ymx <- max(datpd$Qbaseline, datpd$Qout)
  plot(
    datpd$Qbaseline, ylim = c(0,ymx),
    ylab="Flow/WD/PS (cfs)",
    xlab=paste("Model Flow Period",sdate,"to",edate)
  )
  lines(datpd$Qout,col='blue')
  par(new = TRUE)
  ymx <- max(datpd$wd_cumulative_mgd * 1.547, datpd$ps_cumulative_mgd * 1.547)
  plot(
    datpd$wd_cumulative_mgd * 1.547,col='red', 
    axes=FALSE, xlab="", ylab="", ylim=c(0,ymx)
  )
  lines(datpd$ps_cumulative_mgd * 1.547,col='green')
  axis(side = 4)
  mtext(side = 4, line = 3, 'Flow/Demand (cfs)')
  dev.off()
  print(paste("Saved file: ", fname, "with URL", furl))
  vahydro_post_metric_to_scenprop(scenprop$pid, 'dh_image_file', furl, 'fig.flows.all', 0.0, site, token)
  
}



###############################################
# RSEG ELFGEN
###############################################
#GET RSEG HYDROID FROM RSEG MODEL PID
rseg <-getProperty(list(pid=pid), site)
rseg_hydroid<-rseg$featureid

huc_level <- 'huc8'
dataset <- 'VAHydro-EDAS'

elfgen_huc(runid, rseg_hydroid, huc_level, dataset)
###############################################
###############################################







