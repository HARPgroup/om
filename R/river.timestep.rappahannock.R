basepath='/var/www/R';
source(paste(basepath,'config.R',sep='/'))

elid = 258449 #Rappahannock River @ Fall Line RU5_6030_0001
gage_number = '01668000' # RAPPAHANNOCK RIVER NEAR FREDERICKSBURG, VA
alt_gage = "01668000"
startdate <- "1984-10-01"
enddate <- "2014-09-30"
pstartdate <- "2008-04-01"
penddate <- "2008-11-30"

runid = 400
finfo = fn_get_runfile_info(elid, runid, 37, site= omsite)
dat <- om_get_rundata(elid, runid, site= omsite)
mode(dat) <- 'numeric'

# Get and format gage data
gage_data <- gage_import_data_cfs(gage_number, startdate, enddate)
gage_data <- as.zoo(gage_data, as.POSIXct(gage_data$date,tz="EST"))
mode(gage_data) <- 'numeric'
# Low Flows USGS
iflows <- zoo(as.numeric(gage_data$flow), order.by = index(gage_data));
uiflows <- group2(iflows, 'calendar')
Qin90 <- uiflows["90 Day Min"];
l90_usgs <- round(min(Qin90["90 Day Min"]));
# Low Flows VAHydro
iflows <- zoo(as.numeric(dat$Qout), order.by = index(dat));
uiflows <- group2(iflows, 'calendar')
Qin90 <- uiflows["90 Day Min"];
l90_model <- round(min(Qin90["90 Day Min"]));


#limit to period
datpd <- window(dat, start = pstartdate, end = penddate)
gagepd <- window(gage_data, start = pstartdate, end = penddate)
# Plot
ymx <- max(c(max(datpd$Qout),max(gagepd$flow)))
plot(
  datpd$Qout, ylim = c(0,ymx),
  ylab="Flow/WD/PS (cfs)",
  xlab=paste("Model vs USGS",pstartdate,"to",penddate),
  main=paste("Daily Timestep, L90:",l90_usgs,"(u)",l90_model,"(m)")
)
lines(gagepd$flow, col='blue')
dat_x <- rbind(
  c(l90_usgs, quantile(gagepd$flow)),
  c(l90_model, quantile(datpd$Qout))
)
dat_x


# runid:
# 1131 = hourly, 1998-2002
# 1151 - 6-hour, 1998-2002
# 1152 - 4-hour, 1998-2002
# 1153 - 3-hour, 1998-2002
# 1163 - 3-hour, 1984-2014, 2020 demands
# 1363 - 3-hour, 1984-2014, 2040 demands
# 1153 - 3-hour, 1998-2002
runid = 1131
finfo = fn_get_runfile_info(elid, runid, 37, site= omsite)
hdat <- fn_get_runfile(elid, runid, site= omsite,  cached = FALSE)
mode(hdat) <- 'numeric'
# Hourly to Daily flow timeseries
hdat = aggregate(
  hdat,
  as.POSIXct(
    format(
      time(hdat),
      format='%Y/%m/%d'),
    tz='UTC'
  ),
  'mean'
)

#limit to hourly model period
hstart <- min(index(hdat))
hend <- max(index(hdat))
gagehdat <- window(gage_data, start = hstart, end = hend)
gage_data$date

# Low Flows
iflows <- zoo(as.numeric(hdat$Qout), order.by = index(hdat));
uiflows <- group2(iflows, 'water')
Qin90 <- uiflows["90 Day Min"];
l90_hmodel <- round(min(Qin90["90 Day Min"]));
# Low Flows USGS
iflows <- zoo(as.numeric(gagehdat$flow), order.by = index(gagehdat));
uiflows <- group2(iflows, 'water')
Qin90 <- uiflows["90 Day Min"];
l90_husgs <- round(min(Qin90["90 Day Min"]));

datpd <- window(hdat, start = pstartdate, end = penddate)
gagepd <- window(gagehdat, start = pstartdate, end = penddate)
# Plot
ymx <- max(c(max(datpd$Qout),max(gagedat$flow)))
plot(
  datpd$Qout, ylim = c(0,ymx),
  ylab="Flow/WD/PS (cfs)",
  xlab=paste("Model vs USGS",pstartdate,"to",penddate),
  main=paste("1Hr2Daily Timestep, L90:",l90_husgs,"(u)",l90_hmodel,"(m)"),
)
lines(gagepd$flow, col='blue')
quantile(datpd$Qout)
quantile(gagepd$flow)

# Plot
gagepd <-
ymx <- max(c(max(datpd$Qout),max(gagepd$flow)))
plot(
  datpd$Qout, ylim = c(0,ymx),
  ylab="Flow/WD/PS (cfs)",
  xlab=paste("Model vs USGS",pstartdate,"to",penddate),
  main=paste("1Hr2Daily Timestep, L90:",l90_usgs,"(u)",l90_model,"(m)"),
  legend=c('Model', 'USGS')
)
lines(gagepd$flow, col='blue')
