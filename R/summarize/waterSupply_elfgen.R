########################################
###### Elfgen Intake Facility Algorithm
########################################


#### Libraries

library(elfgen)
library(sqldf)
library(ggplot2)
library(stringr)


#### Load in directories and repositories
site <- "http://deq2.bse.vt.edu/d.dh"
save_directory <- "/var/www/html/data/proj3/out"

basepath ='/var/www/R';
source(paste(basepath,'config.R',sep='/'))
source(paste(om_location,'R/summarize/model_2_intake.R',sep='/'))
#site <- "http://deq2.bse.vt.edu/d.dh"

#### Input arguments

# argst <- commandArgs(trailingOnly=T)
# pid <- as.integer(argst[1])
# elid <- as.integer(argst[2])
# runid <- as.integer(argst[3])
runid <- 18
#Additional arguments 
# huc_level <- as.integer(argst[4])
# flow_number <- as.integer(argst[5])   #1-13, 13 is mean annual and the other correspond to the months
# flow_reduction_pct <- as.integer(argst[6])

# pid <- 4827139 #WILDERNESS SERVICE AREA:Rapidan River
# pid <- 4829015 #BLACKSBURG COUNTRY CLUB:North and South Fork (Confluence) Roanoke River
# elid <- ''
# runid <- ''


huc_level <- 'huc8'

#create flow metric df and corresponding month vector
month_flow <- append(month.name, 'Mean Annual')
flow_metric_df <- as.data.frame(cbind('erom_q0001e_jan', 'erom_q0001e_feb',
                                      'erom_q0001e_mar', 'erom_q0001e_apr', 'erom_q0001e_may',
                                      'erom_q0001e_jun', 'erom_q0001e_jul', 'erom_q0001e_aug', 
                                      'erom_q0001e_sept','erom_q0001e_oct', 'erom_q0001e_nov',
                                      'erom_q0001e_dec', 'erom_q0001e_mean'))
flow_number <- 13

flow_reduction_pct <- 10

flow_metric <-as.character(flow_metric_df[flow_number])
##########################################################
#Retrieve intake hydroid from facility:riverseg model pid
# intake.df <- model_2_intake(pid,site)
# hydroid <- intake.df$intake.hydroid

hydroid <- 65056 #directly supplying intake hydroid
##########################################################

#### Take in watershed and mean intake data
site_comparison <- paste(site,'dh-feature-contained-within-export', hydroid, 'watershed', sep = '/')

containing_watersheds <- read.csv(file=site_comparison, header=TRUE, sep=",")

nhd_code <- sqldf(paste("SELECT hydrocode 
             FROM containing_watersheds 
             WHERE ftype = 'nhd_", huc_level,"'", sep = ""))

hydroid2 <- sqldf("SELECT hydroid 
                  FROM containing_watersheds 
                  WHERE ftype 
                  LIKE '%nhdplus%'")

#### Return property dataframe and mean intake

inputs <- list(
  varkey = flow_metric,
  featureid = as.numeric(hydroid2$hydroid),
  entity_type = "dh_feature"
)

dataframe <- getProperty(inputs, site)

mean_intake <- dataframe$propvalue
message(paste("Mean Annual Flow at Intake = ",mean_intake,sep=""))
#### Input parameters for retrieving data from VAHydro

watershed.code <- as.character(nhd_code)
watershed.bundle <- 'watershed'
watershed.ftype <- paste("nhd_", huc_level, sep = "")
x.metric <- flow_metric
y.metric <- 'aqbio_nt_total'
y.sampres <- 'species'

# elfdata_vahydro() function for retrieving data from VAHydro
watershed.df <- elfdata_vahydro(watershed.code,watershed.bundle,watershed.ftype,x.metric,y.metric,y.sampres,site)
# clean_vahydro() function for cleaning data by removing any stations where the ratio of DA:Q is greater than 1000, also aggregates to the maximum richness value at each flow value
watershed.df <- clean_vahydro(watershed.df)

elf_quantile <- 0.80
breakpt <- bkpt_pwit("watershed.df" = watershed.df, "quantile" = elf_quantile, "blo" = mean(watershed.df$x.metric), "bhi" = max(watershed.df$x.metric))  

elf <- elfgen("watershed.df" = watershed.df,
              "quantile" = elf_quantile,
              "breakpt" = breakpt,
              "yaxis_thresh" = 50, 
              "xlabel" = 'Flow [cfs]',
              "ylabel" = "Fish Species Richness")


#### Solving for confidence interval lines

# xdat <- c(elf$plot$data$x_var)
# ydat <- c(elf$plot$data$y_var)
# data <- as.data.frame(elf$plot$data)

uq <- elf$plot$plot_env$upper.quant

upper.lm <- lm(y_var ~ log(x_var), data = uq)

predict <- as.data.frame(predict(upper.lm, newdata = data.frame(x_var = mean_intake), interval = 'confidence'))

species_richness<-elf$stats$m*log(mean_intake)+elf$stats$b

# Comparing predict to actual values
#fit<-as.numeric(predict$fit)
#species_richness<-elf$stats$m*log(mean_intake)+elf$stats$b
#percent_error<-((fit-species_richness)/species_richness)*100


xmin <- min(uq$x_var)
xmax <- max(uq$x_var)

yval1 <- predict(upper.lm, newdata = data.frame(x_var = xmin), interval = 'confidence')
yval2 <- predict(upper.lm, newdata = data.frame(x_var = xmax), interval = 'confidence')

ymin1 <- yval1[2] # bottom left point, line 1
ymax1 <- yval2[3] # top right point, line 1

ymin2 <- yval1[3] # top left point, line 2
ymax2 <- yval2[2] # bottom right point, line 2

m <- elf$stats$m
b <- elf$stats$b
int <- round((m*log(mean_intake) + b),2)      # solving for mean_intake y-value

m1 <- (ymax1-ymin1)/(log(xmax)-log(xmin)) # line 1
b1 <- ymax1-(m1*log(xmax))
int1 <- round((m1*log(mean_intake) + b1),2) 

m2 <- (ymax2-ymin2)/(log(xmax)-log(xmin)) # line 2
b2 <- ymax2 - (m2*log(xmax))
int2 <- round((m2*log(mean_intake) + b2),2) 

# Calculating y max value based on greatest point value or intake y val
if (int > max(watershed.df$NT.TOTAL.UNIQUE)) {
  ymax <- int + 2
} else {
  ymax <- as.numeric(max(watershed.df$NT.TOTAL.UNIQUE)) + 2
}


#### Plot

plt <- elf$plot +
  geom_segment(aes(x = mean_intake, y = -Inf, xend = mean_intake, yend = int), color = 'red', linetype = 'dashed') +
  geom_segment(aes(x = 0, xend = mean_intake, y = int, yend = int), color = 'red', linetype = 'dashed') +
  geom_point(aes(x = mean_intake, y = int, fill = 'Intake'), color = 'red', shape = 'triangle', size = 2) +
  geom_segment(aes(x = xmin, y = (m1 * log(xmin) + b1), xend = xmax, yend = (m1 * log(xmax)) + b1), color = 'blue', linetype = 'dashed') +
  geom_segment(aes(x = xmin, y = (m2 * log(xmin) + b2), xend = xmax, yend = (m2 * log(xmax)) + b2), color = 'blue', linetype = 'dashed') +
  labs(fill = 'Intake Legend', 
       subtitle = paste('Flow Metric:', month_flow[flow_number], 'Flow',sep=' '),
       x=paste(elf$plot$labels$x, '  Breakpt:',round(breakpt,1),sep=' ')) + 
  theme(plot.title = element_text(face = 'bold', vjust = -5)) + 
  ylim(0,ymax)


#### Calculating median percent/absolute richness change

pct_change <- richness_change(elf$stats, "pctchg" = flow_reduction_pct, "xval" = mean_intake)
abs_change <- richness_change(elf$stats, "pctchg" = flow_reduction_pct)

#### Using confidence interval lines to find percent/absolute richness bounds

elf$bound1stats$m <- m1
elf$bound1stats$b <- b1

percent_richness_change_bound1 <- round(richness_change(elf$bound1stats, "pctchg" = flow_reduction_pct, "xval" = mean_intake),2)
abs_richness_change_bound1 <- round(richness_change(elf$bound1stats, "pctchg" = flow_reduction_pct),2)

elf$bound2stats$m <- m2
elf$bound2stats$b <- b2

percent_richness_change_bound2 <- round(richness_change(elf$bound2stats, "pctchg" = flow_reduction_pct, "xval" = mean_intake),2)
abs_richness_change_bound2 <- round(richness_change(elf$bound2stats, "pctchg" = flow_reduction_pct),2)

# checking diffs in pct richness
diff1 <- round((pct_change - percent_richness_change_bound1),2)
diff2 <- round((pct_change - percent_richness_change_bound2),2)

#checking diffs in abs richness
abs_d1 <- round((abs_change - abs_richness_change_bound1),2)
abs_d2 <- round((abs_change - abs_richness_change_bound2),2)

# creating percent change range values
if (abs(diff1) == abs(diff2)) {
  pct_range <- paste('The percent richness change is ',pct_change,' +/-', abs(diff1),sep='')
} else{
  if (diff1 < 0) {
    pct_range <- paste('The percent richness change is ',round(pct_change,2),' +', abs(diff1),'/-', abs(diff2),sep='')
  } else {
    pct_range <- paste('The percent richness change is ',round(pct_change,2),' +', abs(diff2),'/-', abs(diff1),sep='')
  }
}

#creating abs change range values

######## create abs/pct change range vals
#abs
if (abs(abs_d1) == abs(abs_d2)) {
  abs_range <-paste('The absolute richness change is ',round(abs_change,2),' +/-', round(abs(abs_d1),2),sep='')
}else{
  if (abs_d1 < 0) {
    abs_range <- paste('The absolute richness change is ',round(abs_change,2),' +', abs(abs_d1),'/-', abs(abs_d2),sep='')
  } else {
    abs_range <- paste('The absolute richness change is ',round(abs_change,2),' +', abs(abs_d2),'/-', abs(abs_d1),sep='')
  }
}
  

#### Saving

fname <- paste(
  save_directory,
  paste0(
    'fig.elfgen.',
    pid, '.', runid,'.',x.metric, '.', '.png'
  ),
  sep = '/'
)

furl <- paste(
  save_directory,
  paste0(
    'fig.elfgen.',
    watershed.code, '.', x.metric, '.', y.metric, '.png'
  ),
  sep = '/'
)

message(furl)
ggsave(furl, width = 7, height = 5.5)

message(paste("Saved file: ", furl, "with URL", furl))


########## Posting to VAHydro

# GETTING SCENARIO PROPERTY FROM VA HYDRO
scen.propname<-paste0('runid_', runid)

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

#Posting elfgen plot
#vahydro_post_metric_to_scenprop(scenprop$pid, 'dh_image_file', furl, 'fig.elfgen', 0.0, site, token)

#Posting absolute richness change and percent richness
#vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'elfgen_pct_change', pct_range, site, token)
#vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'elfgen_abs_change', abs_range, site, token)

# Posting additional properties
#y val at intake
#vahydro_post_metric_to_scenprop(scenprop$pid, 'om_class_Constant', NULL, 'proj_species_rich', int, site, token)
