#----------------------------------------------
site <- "http://deq2.bse.vt.edu/d.dh"    #Specify the site of interest, either d.bet OR d.dh
#----------------------------------------------
# Load Libraries
basepath='/var/www/R';
source(paste(basepath,'config.R',sep='/'))
library(stringr)
# dirs/URLs
save_directory <- "/var/www/html/data/proj3/out"

# Read Args
argst <- commandArgs(trailingOnly=T)
pid <- as.integer(argst[1])
elid <- as.integer(argst[2])
runid <- as.integer(argst[3])

run_name <- paste0('runid_', runid)
scenprop <- om_get_set_model_run(pid, run_name, site, token)
om_model <- getProperty(list(pid=pid), site, scenprop)

dat <- fn_get_runfile(elid, runid, site= omsite,  cached = FALSE)
mode(dat) <- 'numeric'
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
qm <- mean(dat$Qout)
datdf <- as.data.frame(dat)
yflows <- sqldf(
  "select year, avg(Qout) as Qout 
   from datdf 
   group by year 
   order by year"
)
# Low Flows 
iflows <- zoo(as.numeric(dat$Qout), order.by = index(dat));
uiflows <- group2(iflows, 'water')

myear <- as.integer(min(uiflows$year))
uiflows$yindex <- uiflows$year - myear
s90 <- lm(uiflows$`90 Day Min` ~ uiflows$yindex)
l90_m <- s90$coef[[2]]
l90_b <- s90$coef[[1]]
l90_p <- signif(summary(s90)$coef[2,4], 5)
l90_rsq <- signif(summary(s90)$adj.r.squared, 5)

subverbiage <- paste(
  "Adj R2 = ",round(l90_rsq,2),
  "Intercept =",round(signif(l90_b,5 ),1),
  " Slope =",round(signif(l90_m, 5),2),
  " P =",round(l90_p,3)
)

uiflows$bfq <- (qm * uiflows$`Base index`)
bflm <- lm(`Base index` ~ year, data = uiflows)
summary(bflm)
l90s <- uiflows[,c('year', '90 Day Min', 'Base index', 'bfq')]
names(l90s) <- c('year', 'l90', 'bfi', 'bfq')
bflows <- sqldf(
  "select a.year, 
     a.bfi * b.Qout as bfq, 
     a.l90
   from l90s as a
   left outer join yflows as b
   on (a.year = b.year)
  ")
bflm <- lm(bfq ~ year, data = l90s)
summary(bflm)
bp<- ggplot(l90s, aes(x=year, y=l90)) +
  geom_bar(stat = "identity",color="blue", fill=rgb(0.1,0.4,0.5,0.7))
bp + labs(
  title = om_model$propname,
  subtitle = subverbiage,
  x = 'Year',
  y = 'Lowest 90 Day Flow (cfs)'
) + 
  geom_smooth(method='lm') + 
  geom_smooth(
    aes(y = bfq),
    method='lm',
    fill = 'red',
    alpha = 0.2
  )


fname <- paste(
  save_directory,
  paste0(
    'l90.trend.',
    elid, '.', runid, '.png'
  ),
  sep = '/'
)
furl <- paste(
  save_url,
  paste0(
    'l90.trend.',
    elid, '.', runid, '.png'
  ),
  sep = '/'
)
ggsave(fname,width=7,height=4.75)
vahydro_post_metric_to_scenprop(scenprop$pid, 'dh_image_file', furl, 'l90_trend_plot', 0.0, site, token)
vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'l90_trend', l90_m, site, token)


# slopes, r, p, etc - posted underneath richness_change_abs property 
inputs <- list(
  varkey = 'om_class_Constant',
  propname = 'l90_trend',
  entity_type = 'dh_properties',
  featureid = scenprop$pid)
trend_prop<-getProperty(inputs, site)
vahydro_post_metric_to_scenprop(trend_prop$pid, 'om_class_Constant', NULL, 'p', l90_p, site, token)
vahydro_post_metric_to_scenprop(trend_prop$pid, 'om_class_Constant', NULL, 'rsq', l90_rsq, site, token)
vahydro_post_metric_to_scenprop(trend_prop$pid, 'om_class_Constant', NULL, 'm', l90_m, site, token)
vahydro_post_metric_to_scenprop(trend_prop$pid, 'om_class_Constant', NULL, 'b', l90_b, site, token)

print(1) # to act as positive returning function