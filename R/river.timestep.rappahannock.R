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
mean(dat$Qout)
mean(gage_data$flow)


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
gagdf <- as.data.frame(gagepd)
datdf <- as.data.frame(datpd)
sqldf(
  "
    select count(*), 'model' as dset from datdf where Qout < 664
    UNION
    select count(*), 'model-60' as dset from datdf where (Qout-40) < 664
    UNION
    select count(*), 'model 75%' as dset from datdf where (Qout * 0.75) < 664
    UNION
    select count(*), 'usgs' as dset from gagdf where flow < 664
  "
)

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

# Plot
ymx <- max(c(max(hdat$Qout),max(gagehdat$flow)))
plot(
  hdat$Qout, ylim = c(0,ymx),
  ylab="Flow/WD/PS (cfs)",
  xlab=paste("Model vs USGS",pstartdate,"to",penddate),
  main=paste("1Hr2Daily Timestep, L90:",l90_husgs,"(u)",l90_hmodel,"(m)"),
)
lines(gagehdat$flow, col='blue')
q_cmp <- rbind(
  quantile(hdat$Qout, probs=c(0,0.05, 0.1,0.25,0.5,0.6,0.75, 0.9,1.0)),
  quantile(gagehdat$flow, probs=c(0, 0.05, 0.1,0.25,0.5,0.6,0.75, 0.9,1.0))
)
q_cmp

plot(hdat$Qout ~ gagehdat$flow)
plot( (0.35 * hdat$Qout) ~ gagehdat$flow)

dat2 <- as.data.frame(cbind(hdat$Qout, gagehdat$flow))
colnames(dat2) <- c('vahydro', 'usgs')
dat3 <- as.data.frame(cbind(sort(as.numeric(hdat$Qout)), sort(as.numeric(gagehdat$flow))))
colnames(dat3) <- c('vahydro', 'usgs')
dat2low <- sqldf("select * from dat2 where usgs <= 400")
dat3low <- sqldf("select * from dat3 where usgs <= 800")
plot( (0.35 * dat2low$vahydro) ~ dat2low$usgs)
plot( (0.35 * dat3$vahydro) ~ dat3$usgs)
plot(dat3low$vahydro ~ dat3low$usgs)
plot(dat3$vahydro ~ dat3$usgs)
plot( dat2low$vahydro ~ dat2low$usgs)
sort(dat2low$usgs)

dat4low <- sqldf("select * from dat3 where usgs <= 2000")

lm3 <- lm(
#  dat3low$usgs ~ dat3low$vahydro
  dat3low$usgs ~ I(dat3low$vahydro - 40)
#  dat3low$usgs ~ dat3low$vahydro + I(dat3low$vahydro ^ 2)
)
summary(lm3)

mean(hdat$Qout)
mean(gagehdat$flow)
hydroTSM::fdc(cbind(hdat$Qout, gagehdat$flow))


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
