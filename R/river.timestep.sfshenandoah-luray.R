basepath='/var/www/R';
source(paste(basepath,'config.R',sep='/'))

elid = 231665  # PS5_5240_5200
gage_number = '01628500' #USGS 01628500 S F SHENANDOAH RIVER NEAR LYNNWOOD, VA 
startdate <- "2002-04-01" # this gage was out of commission from 1986-2002
enddate <- "2002-11-30"

# Get and format gage data
gage_data <- gage_import_data_cfs(gage_number, startdate, enddate)
gage_data <- as.zoo(gage_data, as.POSIXct(gage_data$date,tz="EST"))
mode(gage_data) <- 'numeric' 
# Low Flows 
iflows <- zoo(as.numeric(gage_data$flow), order.by = index(gage_data));
uiflows <- group2(iflows, 'calendar')
Qin30 <- uiflows["30 Day Min"];
l30_usgs <- round(min(Qin30["30 Day Min"]));

runid = 11
finfo = fn_get_runfile_info(elid, runid, 37, site= omsite)
dat <- fn_get_runfile(elid, runid, site= omsite,  cached = FALSE)
mode(dat) <- 'numeric'
#limit to period
datpd <- window(dat, start = startdate, end = enddate)
# Low Flows 
iflows <- zoo(as.numeric(datpd$Qout), order.by = index(datpd));
uiflows <- group2(iflows, 'calendar')
Qin30 <- uiflows["30 Day Min"];
l30_model <- round(min(Qin30["30 Day Min"]));

# Plot
ymx <- max(datpd$Qout,gage_data$flow)
plot(
  datpd$Qout, ylim = c(0,ymx),
  ylab="Flow/WD/PS (cfs)",
  xlab=paste("Model vs USGS",startdate,"to",enddate),
  main=paste("Daily Timestep, L30:",l30_usgs,"(u)",l30_model,"(m)")
)
lines(gage_data$flow, col='blue')


# runid: 
# 1131 = hourly, 1998-2002
runid = 1131
finfo = fn_get_runfile_info(elid, runid, 37, site= omsite)
dat <- fn_get_runfile(elid, runid, site= omsite,  cached = FALSE)
mode(dat) <- 'numeric'
#limit to period
datpd <- window(dat, start = startdate, end = enddate)


# Plot
ymx <- max(datpd$Qout,gage_data$flow)
plot(
  datpd$Qout, ylim = c(0,ymx),
  ylab="Flow/WD/PS (cfs)",
  xlab=paste("Model vs USGS",startdate,"to",enddate),
  main="Hourly Model Timestep"
)
lines(gage_data$flow, col='blue')


# Hourly to Daily flow timeseries
datpd = aggregate(
  datpd,
  as.POSIXct(
    format(
      time(datpd), 
      format='%Y/%m/%d'),
    tz='UTC'
  ),
  'mean'
)
# Low Flows 
iflows <- zoo(as.numeric(datpd$Qout), order.by = index(datpd));
uiflows <- group2(iflows, 'calendar')
Qin30 <- uiflows["30 Day Min"];
l30_model <- round(min(Qin30["30 Day Min"]));

# Plot
ymx <- max(datpd$Qout,gage_data$flow)
plot(
  datpd$Qout, ylim = c(0,ymx),
  ylab="Flow/WD/PS (cfs)",
  xlab=paste("Model vs USGS",startdate,"to",enddate),
  main=paste("Hr2Daily Timestep, L30:",l30_usgs,"(u)",l30_model,"(m)"),
  legend=c('Model', 'USGS')
)
lines(gage_data$flow, col='blue')

# 1151 - 6-hour, 1998-2002
# 1152 - 4-hour, 1998-2002
# 1153 - 3-hour, 1998-2002
# 1163 - 3-hour, 1984-2014, 2020 demands
# 1363 - 3-hour, 1984-2014, 2040 demands
# 1153 - 3-hour, 1998-2002
runid = 1363
finfo = fn_get_runfile_info(elid, runid, 37, site= omsite)
dat <- fn_get_runfile(elid, runid, site= omsite,  cached = FALSE)
mode(dat) <- 'numeric'
# analyze

#limit to period
datpd <- window(dat, start = startdate, end = enddate)


# Plot
ymx <- max(datpd$Qout,gage_data$flow)
plot(
  datpd$Qout, ylim = c(0,ymx),
  ylab="Flow/WD/PS (cfs)",
  xlab=paste("Model vs USGS",startdate,"to",enddate),
  main="3-Hour Model Timestep"
)
lines(gage_data$flow, col='blue')

# Hourly to Daily flow timeseries
datpd = aggregate(
  datpd,
  as.POSIXct(
    format(
      time(datpd), 
      format='%Y/%m/%d'),
    tz='UTC'
  ),
  'mean'
)
# Low Flows 
iflows <- zoo(as.numeric(datpd$Qout), order.by = index(datpd));
uiflows <- group2(iflows, 'calendar')
Qin30 <- uiflows["30 Day Min"];
l30_model <- round(min(Qin30["30 Day Min"]));

# Plot
ymx <- max(datpd$Qout,gage_data$flow)
plot(
  datpd$Qout, ylim = c(0,ymx),
  ylab="Flow/WD/PS (cfs)",
  xlab=paste("Model vs USGS",startdate,"to",enddate),
  main=paste("3Hr2Daily Timestep, L30:",l30_usgs,"(u)",l30_model,"(m)"),
  legend=c('Model', 'USGS')
)
lines(gage_data$flow, col='blue')
