basepath='/var/www/R';
source(paste(basepath,'config.R',sep='/'))

elid = 337722 #Rappahannock River @ Fall Line RU5_6030_0001
gage_number = '02031000' # RAPPAHANNOCK RIVER NEAR FREDERICKSBURG, VA
startdate <- "1984-10-01"
enddate <- "2014-09-30"
pstartdate <- "2010-01-01"
penddate <- "2010-11-30"

runid = 11
finfo = fn_get_runfile_info(elid, runid, 37, site= omsite)
dat <- om_get_rundata(elid, runid, site= omsite)
mode(dat) <- 'numeric'

# Get and format gage data
gage_data <- gage_import_data_cfs(gage_number, startdate, enddate)
gage_data <- as.zoo(gage_data, as.POSIXct(gage_data$date,tz="EST"))
mode(gage_data) <- 'numeric'
# Low Flows USGS
iflows <- zoo(as.numeric(gage_data$flow), order.by = index(gage_data));
guiflows <- group2(iflows, 'calendar')
gQmin90 <- guiflows["90 Day Min"];
l90_usgs <- round(min(Qin90["90 Day Min"]));
# Low Flows VAHydro
iflows <- zoo(as.numeric(dat$Qout), order.by = index(dat));
muiflows <- group2(iflows, 'calendar')
mQmin90 <- muiflows["90 Day Min"];
l90_model <- round(min(Qin90["90 Day Min"]));
mean(dat$Qout)
mean(gage_data$flow)

plot(mQmin90$`90 Day Min` ~ gQmin90$`90 Day Min` )
abline()
lf90_lm <- lm(mQmin90$`90 Day Min` ~ gQmin90$`90 Day Min` )
summary(lf90_lm)

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

# at dam
runid = 11
delid = 352054
finfo = fn_get_runfile_info(delid, runid, 37, site= omsite)
hdat <- fn_get_runfile(delid, runid, site= omsite,  cached = FALSE)
mode(hdat) <- 'numeric'
hdatdf <- as.data.frame(hdat)

quantile(hdat$impoundment_Qin)
quantile(hdat$impoundment_Qout)
sqldf("
  select season,
    avg(Qin) as Qin,
    avg(Qout),
    avg(demand),
    count(*) as numdays,
    sum(spilling) as spilldays,
    avg( (Qout - Qin) / Qin) as mean_daily_pct_diff
  from (
    select CASE
      WHEN month in (1,2,3) THEN 1
      WHEN month in (4,5,6) THEN 2
      WHEN month in (7,8,9) THEN 3
      WHEN month in (10,11,12) THEN 4
      END as season,
    CASE
      WHEN impoundment_Qout > release THEN 1
      ELSE 0
    END as spilling,
    impoundment_demand as demand,
    impoundment_Qin as Qin,
    impoundment_Qout as Qout
    from hdatdf
  ) as foo
  group by season
")

# spill/no-spill stats
spill_days <- sqldf("
  select year, season,
    round(avg(Qin)) as Qin,
    round(avg(Qout)) as Qout,
    round(avg(demand),2) as demand,
    count(*) as numdays,
    sum(spilling) as spilldays,
    count(*) - sum(spilling) as nospill_days,
    round(100.0 * avg( (Qout - Qin) / Qin)) as cu_pct
  from (
    select year, CASE
      WHEN month in (1,2,3) THEN 1
      WHEN month in (4,5,6) THEN 2
      WHEN month in (7,8,9) THEN 3
      WHEN month in (10,11,12) THEN 4
      END as season,
    CASE
      WHEN impoundment_Qout > release THEN 1
      ELSE 0
    END as spilling,
    impoundment_demand as demand,
    impoundment_Qin as Qin,
    impoundment_Qout as Qout
    from hdatdf
  ) as foo
  group by season, year
")

# spill summary

# spill/no-spill stats
spill_stats <- sqldf("
  select season,
    round(avg(Qin)) as Qin,
    round(avg(Qout)) as Qout,
    round(avg(demand),2) as demand,
    sum(numdays) as numdays,
    sum(spilldays) as spilldays,
    sum(numdays) - sum(spilldays) as nospill_days,
    round(100.0 * avg( (Qout - Qin) / Qin)) as cu_pct
  from spill_days
  group by season
")
# NOTE: calculating the CU percent can be done 2 different ways
#  in general, the way we would do it is as a function of the
#  entire period totals for in and outflow, and then
#  calcualte the percent of the 2
#  However, if we calculate the % change each day, then take a mean
#  of that, we get a different value
#  so

# spill/no-spill stats
sqldf("
  select season,
    round(avg(Qin)) as Qin,
    round(avg(Qout)) as Qout,
    round(avg(demand)) as wd_mgd,
    count(*) as numdays,
    sum(spilling) as spilldays,
    count(*) - sum(spilling) as nospill_days,
    round(100.0 * avg( (Qout - Qin) / Qin)) as cu_pct,
    round(100.0 *  (avg(Qout) - avg(Qin)) / avg(Qin)) as cu_pct2
  from (
    select CASE
      WHEN month in (1,2,3) THEN 1
      WHEN month in (4,5,6) THEN 2
      WHEN month in (7,8,9) THEN 3
      WHEN month in (10,11,12) THEN 4
      END as season,
    CASE
      WHEN impoundment_Qout > release THEN 1
      ELSE 0
    END as spilling,
    impoundment_demand as demand,
    impoundment_Qin as Qin,
    impoundment_Qout as Qout
    from hdatdf
  ) as foo
  group by season
")

spill_stats

