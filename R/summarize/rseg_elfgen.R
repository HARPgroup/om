library(testit) #USED FOR has_warning())
library(quantreg) #USED FOR rq())

#LOAD ELFGEN FUNCTIONS
source(paste(elfgen_location,'R/elfdata-vahydro.R',sep='/'))
source(paste(elfgen_location,'R/clean-vahydro.R',sep='/'))
source(paste(elfgen_location,'R/elfgen.R',sep='/'))
source(paste(elfgen_location,'R/richness-change.R',sep='/'))

elfgen_confidence <- function(elf,rseg.name,outlet_flow,yaxis_thresh,cuf){
  #Confidence Interval information
  uq <- elf$plot$plot_env$upper.quant

  upper.lm <- lm(y_var ~ log(x_var), data = uq)

  predict <- as.data.frame(predict(upper.lm, newdata = data.frame(x_var = outlet_flow), interval = 'confidence'))

  species_richness<-elf$stats$m*log(outlet_flow)+elf$stats$b

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
  int <- round((m*log(outlet_flow) + b),2)      # solving for outlet_flow y-value

  m1 <- (ymax1-ymin1)/(log(xmax)-log(xmin)) # line 1
  b1 <- ymax1-(m1*log(xmax))

  m2 <- (ymax2-ymin2)/(log(xmax)-log(xmin)) # line 2
  b2 <- ymax2 - (m2*log(xmax))

  # Calculating y max value based on greatest point value or intake y val
  #if (int > max(watershed.df$NT.TOTAL.UNIQUE)) {
  #  ymax <- int + 2
  #} else {
  #  ymax <- as.numeric(max(watershed.df$NT.TOTAL.UNIQUE)) + 2
  #}

  #Calculating median percent/absolute richness change
  pct_change <- round(richness_change(elf$stats, "pctchg" = cuf*100, "xval" = outlet_flow),2)
  abs_change <- round(richness_change(elf$stats, "pctchg" = cuf*100),2)

  #Using confidence interval lines to find percent/absolute richness bounds
  elf$bound1stats$m <- m1
  elf$bound1stats$b <- b1

  percent_richness_change_bound1 <- round(richness_change(elf$bound1stats, "pctchg" = cuf*100, "xval" = outlet_flow),2)
  abs_richness_change_bound1 <- round(richness_change(elf$bound1stats, "pctchg" = cuf*100),2)

  elf$bound2stats$m <- m2
  elf$bound2stats$b <- b2

  percent_richness_change_bound2 <- round(richness_change(elf$bound2stats, "pctchg" = cuf*100, "xval" = outlet_flow),2)
  abs_richness_change_bound2 <- round(richness_change(elf$bound2stats, "pctchg" = cuf*100),2)

  #checking diffs in pct richness
  pct_d1 <- round((pct_change - percent_richness_change_bound1),2)
  pct_d2 <- round((pct_change - percent_richness_change_bound2),2)

  #checking diffs in abs richness
  abs_d1 <- round((abs_change - abs_richness_change_bound1),2)
  abs_d2 <- round((abs_change - abs_richness_change_bound2),2)

  plt <- elf$plot +
    geom_segment(aes(x = outlet_flow, y = -Inf, xend = outlet_flow, yend = int), color = 'red', linetype = 'dashed', show.legend = FALSE) +
    geom_segment(aes(x = 0, xend = outlet_flow, y = int, yend = int), color = 'red', linetype = 'dashed', show.legend = FALSE) +
    geom_point(aes(x = outlet_flow, y = int, fill = paste("River Segment Outlet\n(MAF = ",outlet_flow," cfs)",sep="")), color = 'red', shape = 'triangle', size = 2) +
    geom_segment(aes(x = xmin, y = (m1 * log(xmin) + b1), xend = xmax, yend = (m1 * log(xmax) + b1)), color = 'blue', linetype = 'dashed', show.legend = FALSE) +
    geom_segment(aes(x = xmin, y = (m2 * log(xmin) + b2), xend = xmax, yend = (m2 * log(xmax) + b2)), color = 'blue', linetype = 'dashed', show.legend = FALSE) +

    #Modify River Segment Outlet legend
    guides(fill = guide_legend(override.aes = list(color="red")))+

    labs(fill = '',
         #x=paste(elf$plot$labels$x, '  Breakpt:',elf$stats$breakpt,sep=' '),
         x=paste(elf$plot$labels$x, '\nBreakpoint: ',elf$stats$breakpt,' cfs',sep=''),
         #caption = paste('Breakpoint: ',elf$stats$breakpt,sep=' '),
         title = paste('Containing Hydrologic Unit: ',elf$stats$watershed,'\n',sep=' '),
         subtitle = paste('River Segment: ',rseg.name,sep=' ')
         ) +
    theme(plot.title = element_text(face = 'bold', vjust = -5))
    # theme(plot.title = element_text(face = 'bold', vjust = -5),
    #       legend.key = element_rect(fill = "grey")) +
    ylim(0,yaxis_thresh)


  confidence<-list(plot = plt, df = data.frame(pct_change,pct_d1,pct_d2, abs_change,abs_d1,abs_d2))
  return(confidence)

}

elfgen_huc <- function(
  runid, hydroid, huc_level, dataset, scenprop, ds,
  save_directory = '/var/www/html/data/proj3/out',
  save_url = 'http://deq1.bse.vt.edu:81/data/proj3/out',
  site = 'http://deq1.bse.vt.edu/d.dh'
  ) {
  breakpt <- 530
  x.metric <- 'erom_q0001e_mean'
  y.metric <- 'aqbio_nt_total'
  y.sampres <- 'species'
  quantile <- 0.8
  yaxis_thresh <- 53


  #Determine watershed outlet nhd+ segment and hydroid
  nhdplus_views <- paste(site,'dh-feature-containing-export', hydroid, 'watershed/nhdplus/nhdp_drainage_sqmi',  sep = '/')
  nhdplus_df <- read.csv(file=nhdplus_views, header=TRUE, sep=",")
  message(paste("length(nhdplus_df): ", length(nhdplus_df[,1])))
  
  if (dataset == 'IchthyMaps'){
    dataname='Ichthy'
  }else{
    dataname='EDAS'
  }
  
  # prevents error "Error in if (is.na(config[[i]])) { : argument is of length zero"
  # error caused when no nhdplus segs are returned -> often for location outside of VA
  if(length(nhdplus_df[,1]) < 1) {

    elfgen_container <- RomProperty$new(
      ds, list(
        varkey="om_class_Constant", 
        featureid=scenprop$pid,
        entity_type='dh_properties',
        propname = as.character(paste('elfgen_', dataname,'_', huc_level, sep=''))
      ),
      TRUE
    )
    elfgen_container$propcode <- as.character('no nhdplus found')
    elfgen_container$save(TRUE)

    message('No nhdplus segment found for this location')
    return(NULL)
  }
  
  
  #MORE EFFICIENT SQL
  outlet_nhdplus_segment <-sqldf("select * from nhdplus_df ORDER BY propvalue DESC LIMIT 1")
  hydroid_out <- outlet_nhdplus_segment$hydroid
  code_out <- outlet_nhdplus_segment$hydrocode
  rseg.name <- outlet_nhdplus_segment$Containing_Feature_Name



  # DEBUG --------------------------------------------------------------------------------------
  # message(hydroid)

  # vector of rseg hydroids where the approach "the nhdplus seg with the greatest DA" fails
  # because nhdplus feature overlaps at the outlet of the rseg
  mis_assigned_hydroid_out <- c(68096,67769,67842,68005,68264)
  #loop through and re-assign these rsegs with an appropriate outlet nhdplus segment
  if (hydroid %in% mis_assigned_hydroid_out) {

      hydroid_out <- case_when(hydroid == 68096 ~ 304639,
                               hydroid == 67769 ~ 335097,
                               hydroid == 67842 ~ 298104,
                               hydroid == 68005 ~ 331521,
                               hydroid == 68264 ~ 314256
                               )

      code_out <-    case_when(hydroid == 68096 ~ 8616505,
                               hydroid == 67769 ~ 5908205,
                               hydroid == 67842 ~ 8572971,
                               hydroid == 68005 ~ 434106,
                               hydroid == 68264 ~ 8545673
                               )

  }

  # message(hydroid_out)
  # --------------------------------------------------------------------------------------------

  #Determine cumulative consumptive use fraction for the river segment
  inputs <- list(
    varkey = 'om_class_Constant',
    propname = 'consumptive_use_frac',
    entity_type = 'dh_properties',
    featureid = scenprop$pid,
    bundle = "dh_properties"
  )
  prop_cuf <- RomProperty$new(ds, inputs, TRUE)
  cuf <- prop_cuf$propvalue

  #Pulls mean annual outlet flow
  inputs <- list(
    varkey = x.metric,
    featureid = as.numeric(hydroid_out),
    entity_type = "dh_feature"
  )

  prop_flow <-  RomProperty$new(ds, inputs, TRUE)
  outlet_flow <- prop_flow$propvalue #outlet flow as erom_q0001e_mean of nhdplus segment

  #Determines huc of interest for outlet nhd+ segment
  site_comparison <- paste(site,'dh-feature-contained-within-export', hydroid_out, 'watershed', sep = '/')
  containing_watersheds <- read.csv(file=site_comparison, header=TRUE, sep=",")

  nhd_code <- sqldf(paste("SELECT hydrocode
             FROM containing_watersheds
             WHERE ftype = 'nhd_", huc_level,"'", sep = "" ))


  #HUC Section---------------------------------------------------------------------------
  watershed.code <- as.character(nhd_code$hydrocode)
  watershed.bundle <- 'watershed'
  watershed.ftype <- paste("nhd_", huc_level, sep = "") #watershed.ftpe[i] when creating function
  datasite <- site

  if (dataset == 'IchthyMaps'){
    #if loop below works only for huc:6,8,10 due to naming convention in containing_watershed
    if(huc_level == 'huc8'){
      watershed.code <- str_sub(watershed.code, -8,-1)
      }
    watershed.df <- elfdata(watershed.code)
  }else{
    # elfdata_vahydro() function for retrieving data from VAHydro
    watershed.df <- elfdata_vahydro(watershed.code,watershed.bundle,watershed.ftype,x.metric,y.metric,y.sampres,datasite)
    # clean_vahydro() function for cleaning data by removing any stations where the ratio of DA:Q is greater than 1000, also aggregates to the maximum richness value at each flow value
    watershed.df <- clean_vahydro(watershed.df)
  }

  #######################################################
  # run elfgen (with tryCatch to capture any errors originating from elfgen)
  an.error.occured <- FALSE
  tryCatch({
    elf <- elfgen(
      "watershed.df" = watershed.df,
      "quantile" = quantile,
      "breakpt" = breakpt,
      "yaxis_thresh" = yaxis_thresh,
      "xlabel" = "Mean Annual Flow (ft3/s)",
      "ylabel" = "Fish Species Richness"
    )
    # message(elfgen_result)
  }
  , error = function(e) {
    an.error.occured <<- e
  })

  # log errors that originate from within elfgen package functions
  if(!isFALSE(an.error.occured)) {
    
    elfgen_container <- RomProperty$new(
      ds, list(
        varkey="om_class_Constant", 
        featureid=scenprop$pid,
        entity_type='dh_properties',
        propname = as.character(paste('elfgen_', dataname,'_', huc_level, sep=''))
      ),
      TRUE
    )
    elfgen_container$propcode <- as.character(an.error.occured$message)
    elfgen_container$save(TRUE)
    
    message(an.error.occured)
    return("NULL")
  } 
  #######################################################
  
  
  confidence <- elfgen_confidence(elf,rseg.name,outlet_flow,yaxis_thresh,cuf)

  
  #--------------------------------------------------------------
  # create elfgen container prop for housing all elfgen stats
  elfgen_container <- RomProperty$new(
    ds, list(
      varkey="om_class_Constant", 
      featureid=scenprop$pid,
      entity_type='dh_properties',
      propname = as.character(paste('elfgen_', dataname,'_', huc_level, sep=''))
    ),
    TRUE
  )
  elfgen_container$propcode <- as.character(watershed.code)
  elfgen_container$save(TRUE)
  #--------------------------------------------------------------
  
  message("POSTING PROPERTIES TO VAHYDRO...")

  vahydro_post_metric_to_scenprop(elfgen_container$pid, 'om_class_Constant', NULL, 'richness_change_abs', confidence$df$abs_change, ds)

  #Absolute change confidence interval bounds - posted underneath richness_change_abs property
  inputs <- list(
    varkey = 'om_class_Constant',
    propname = 'dataset',
    entity_type = 'dh_properties',
    propcode = dataset,
    featureid = elfgen_container$pid)

  prop_ds <- RomProperty$new(ds, inputs, TRUE)
  prop_ds$save(TRUE)

  #Absolute change confidence interval bounds - posted underneath richness_change_abs property
  inputs <- list(
    varkey = 'om_class_Constant',
    propname = 'richness_change_abs',
    entity_type = 'dh_properties',
    featureid = elfgen_container$pid)

  prop_abs <- RomProperty$new(ds, inputs, TRUE)
  prop_abs$save(TRUE)

  vahydro_post_metric_to_scenprop(prop_abs$pid, 'om_class_Constant', NULL, 'upper_confidence', confidence$df$abs_d1, ds) #flipped and negated to match negative richness change value
  vahydro_post_metric_to_scenprop(prop_abs$pid, 'om_class_Constant', NULL, 'lower_confidence', confidence$df$abs_d2, ds)

  #Percent change branch - posted underneath elfgen_richness_change_huc_level scenario property
  vahydro_post_metric_to_scenprop(elfgen_container$pid, 'om_class_Constant', NULL, 'richness_change_pct', confidence$df$pct_change, ds)

  #Percent change confidence interval bounds - posted underneath richness_change_pct property
  inputs <- list(
    varkey = 'om_class_Constant',
    propname = 'richness_change_pct',
    entity_type = 'dh_properties',
    featureid = elfgen_container$pid)

  prop_pct <- RomProperty$new(ds, inputs, TRUE)
  prop_pct$save(TRUE)

  vahydro_post_metric_to_scenprop(prop_pct$pid, 'om_class_Constant', NULL, 'upper_confidence', confidence$df$pct_d1, ds) #flipped similar to vahydro
  vahydro_post_metric_to_scenprop(prop_pct$pid, 'om_class_Constant', NULL, 'lower_confidence', confidence$df$pct_d2, ds)

  #Elf$stats posts - posted underneath elfgen_richness_change_huc_level scenario property-----------------------
  vahydro_post_metric_to_scenprop(elfgen_container$pid, 'stat_quantreg_bkpt', NULL, 'breakpt', elf$stats$breakpt, ds)
  vahydro_post_metric_to_scenprop(elfgen_container$pid, 'stat_quantreg_qu', NULL, 'quantile', elf$stats$quantile, ds)
  vahydro_post_metric_to_scenprop(elfgen_container$pid, 'stat_quantreg_m', NULL, 'm', elf$stats$m, ds)
  vahydro_post_metric_to_scenprop(elfgen_container$pid, 'stat_quantreg_b', NULL, 'b', elf$stats$b, ds)
  vahydro_post_metric_to_scenprop(elfgen_container$pid, 'stat_quantreg_rsq', NULL, 'rsquared', elf$stats$rsquared, ds)
  vahydro_post_metric_to_scenprop(elfgen_container$pid, 'stat_quantreg_adj_rsq', NULL, 'rsquared_adj', elf$stats$rsquared_adj, ds)
  vahydro_post_metric_to_scenprop(elfgen_container$pid, 'stat_quantreg_p', NULL, 'p', elf$stats$p, ds)
  vahydro_post_metric_to_scenprop(elfgen_container$pid, 'stat_quantreg_n_tot', NULL, 'n_total', elf$stats$n_total, ds)
  vahydro_post_metric_to_scenprop(elfgen_container$pid, 'stat_quantreg_n_sub', NULL, 'n_subset', elf$stats$n_subset, ds)
  vahydro_post_metric_to_scenprop(elfgen_container$pid, 'stat_quantreg_n', NULL, 'n_subset_upper', elf$stats$n_subset_upper, ds)
  vahydro_post_metric_to_scenprop(elfgen_container$pid, 'erom_q0001e_mean', code_out, 'erom_q0001e_mean', outlet_flow, ds)

  #Elf$plot post - posted underneath elfgen_richness_change_huc_level scenario property------------
  dR10 <- richness_change(elf$stats, "pctchg" = 10)
  dR20 <- richness_change(elf$stats, "pctchg" = 20)
  vahydro_post_metric_to_scenprop(elfgen_container$pid, 'om_class_Constant', NULL, 'dNT_10pct', dR10, ds)
  vahydro_post_metric_to_scenprop(elfgen_container$pid, 'om_class_Constant', NULL, 'dNT_20pct', dR20, ds)
  

  #Image saving & naming
  fname <- paste(
    save_directory,
    paste0(
      'fig.elfgen.',
      elfgen_container$pid,'.png'
    ),
    sep = '/'
  )

  furl <- paste(
    save_url,paste0(
    'fig.elfgen.',
    elfgen_container$pid,'.png'
  ),
    sep = '/'
  )

  message(fname)
  ggsave(fname, plot = confidence$plot, width = 7, height = 5.5)

  message(paste("Saved file: ", fname, "with URL", furl))
  vahydro_post_metric_to_scenprop(elfgen_container$pid, 'dh_image_file', furl, 'fig.elfgen', 0.0, ds)

  message('elf plot generated')
  return(confidence$plot)
}
