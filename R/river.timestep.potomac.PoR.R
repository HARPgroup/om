basepath='/var/www/R';
source(paste(basepath,'config.R',sep='/'))

elid = 233997  # PM7_4290_4200
gage_number = '01638500' #USGS  POTOMAC RIVER AT POINT OF ROCKS, MD
gage_data <- gage_import_data_cfs(gage_number, '1895-10-01', '2020-09-30')
gage_data <- as.zoo(gage_data, as.POSIXct(gage_data$date,tz="EST"))
gdat <- as.data.frame(gage_data)
iflows <- zoo(as.numeric(gage_data$flow), order.by = index(gage_data));
uiflows <- group2(iflows, 'calendar')
barplot(uiflows$`90 Day Min` ~ uiflows$year)
myear <- as.integer(min(uiflows$year))
uiflows$yindex <- uiflows$year - myear
s90 <- lm(uiflows$`90 Day Min` ~ uiflows$yindex)
abline(s90)
summary(s90)

# now just the event in questio
startdate <- "1999-04-01" # this gage was out of commission from 1986-2002
enddate <- "1999-11-30"

# Get and format gage data
gage_data <- gage_import_data_cfs(gage_number, startdate, enddate)
gage_data <- as.zoo(gage_data, as.POSIXct(gage_data$date,tz="EST"))
mode(gage_data) <- 'numeric'
# Low Flows
iflows <- zoo(as.numeric(gage_data$flow), order.by = index(gage_data));
uiflows <- group2(iflows, 'calendar')
Qin30 <- uiflows["30 Day Min"];
l30_usgs <- round(min(Qin30["30 Day Min"]));
Qin90 <- uiflows["90 Day Min"];
l90_usgs <- round(min(Qin90["90 Day Min"]));


runid = 11
finfo = fn_get_runfile_info(elid, runid, 37, site= omsite)
dat <- fn_get_runfile(elid, runid, site= omsite,  cached = FALSE)
mode(dat) <- 'numeric'
# *******************************************************************
# Full Period Low Flows
# *******************************************************************
iflows <- zoo(as.numeric(dat$Qout), order.by = index(dat));
uiflows <- group2(iflows, 'calendar')
barplot(uiflows$`90 Day Min` ~ uiflows$year)
myear <- as.integer(min(uiflows$year))
uiflows$yindex <- uiflows$year - myear
s90 <- lm(uiflows$`90 Day Min` ~ uiflows$yindex)
abline(s90)
summary(s90)
# *******************************************************************
# END - Full Period Low Flows
# *******************************************************************
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


runid = 11
finfo = fn_get_runfile_info(elid, 11, 37, site= omsite)
dat11 <- fn_get_runfile(elid, runid, site= omsite,  cached = FALSE)
mode(dat11) <- 'numeric'
dat11pd <- window(dat11, start = '1998-10-01', end = '2002-12-31')

finfo = fn_get_runfile_info(elid, 1163, 37, site= omsite)
dat <- fn_get_runfile(elid, 1363, site= omsite,  cached = FALSE)
mode(dat) <- 'numeric'
#limit to period
datpd <- window(dat, start = '1998-10-01', end = '2002-12-31')
gdata_9802 <- window(gage_data, start = '1998-10-01', end = '2002-12-31')
gdata_9802$flow <- as.numeric(gdata_9802$flow)
mode(gdata_9802) <- 'numeric'
# Plot
ymx <- max(c(max(datpd$Qout),max(gdata_9802$flow)))
plot(
  datpd$Qout, ylim = c(0,ymx),
  ylab="Flow/WD/PS (cfs)",
  xlab=paste("Model vs USGS",startdate,"to",enddate),
  main="3-Hour Model Timestep"
)
lines(gdata_9802$flow, col='blue')

# runid:
# 1131 = hourly, 1998-2002
runid = 1131
finfo = fn_get_runfile_info(elid, runid, 37, site= omsite)
dat <- fn_get_runfile(elid, runid, site= omsite,  cached = FALSE)
mode(dat) <- 'numeric'
#limit to period
datpd <- window(dat, start = '1998-10-01', end = '2002-12-31')
gdata_9802 <- window(gage_data, start = '1998-10-01', end = '2002-12-31')
gdata_9802$flow <- as.numeric(gdata_9802$flow)
mode(gdata_9802) <- 'numeric'
# Plot
ymx <- max(c(max(datpd$Qout),max(gdata_9802$flow)))
plot(
  datpd$Qout, ylim = c(0,ymx),
  ylab="Flow/WD/PS (cfs)",
  xlab=paste("Model vs USGS",startdate,"to",enddate),
  main="Hourly Model Timestep"
)
lines(gdata_9802$flow, col='blue')
iflows <- zoo(as.numeric(datpd$Qout), order.by = index(datpd));
uiflows <- group2(iflows, 'calendar')
Qin30 <- uiflows["30 Day Min"];
l30_model <- round(min(Qin30["30 Day Min"]));


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
