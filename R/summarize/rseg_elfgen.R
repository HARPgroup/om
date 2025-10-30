suppressPackageStartupMessages(library(testit)) #USED FOR has_warning())
suppressPackageStartupMessages(library(quantreg)) #USED FOR rq())
suppressPackageStartupMessages(library(nhdplusTools))
suppressPackageStartupMessages(library(ggplot2))

#LOAD ELFGEN FUNCTIONS
source(paste(elfgen_location,'R/elfdata-vahydro.R',sep='/'))
source(paste(elfgen_location,'R/clean-vahydro.R',sep='/'))
source(paste(elfgen_location,'R/elfgen.R',sep='/'))
source(paste(elfgen_location,'R/richness-change.R',sep='/'))

elfgen_confidence <- function(elf,rseg.name, outlet_flow,yaxis_thresh,cuf, outlet_col="MAF"){
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
    geom_point(aes(x = outlet_flow, y = int, fill = paste("River Segment Outlet\n(", outlet_col,"=",outlet_flow,"cfs)",sep="")), color = 'red', shape = 'triangle', size = 2) +
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

elfgen_varkey_nhd_col <- function(varkey, dataset='dh_nhd') {
  
  nhd_map <- list(
    'erom_q0001e_jan' = list(dh_nhd='qa_01',elfgen='Q01'),
    'erom_q0001e_feb' = list(dh_nhd='qa_02',elfgen='Q02'),
    'erom_q0001e_mar' = list(dh_nhd='qa_03',elfgen='Q03'),
    'erom_q0001e_apr' = list(dh_nhd='qa_04',elfgen='Q04'),
    'erom_q0001e_may' = list(dh_nhd='qa_05',elfgen='Q05'),
    'erom_q0001e_june' = list(dh_nhd='qa_06',elfgen='Q06'),
    'erom_q0001e_july' = list(dh_nhd='qa_07',elfgen='Q07'),
    'erom_q0001e_aug' = list(dh_nhd='qa_08',elfgen='Q08'),
    'erom_q0001e_sept' = list(dh_nhd='qa_09',elfgen='Q09'),
    'erom_q0001e_oct' = list(dh_nhd='qa_10',elfgen='Q10'),
    'erom_q0001e_nov' = list(dh_nhd='qa_11',elfgen='Q11'),
    'erom_q0001e_dec' = list(dh_nhd='qa_12',elfgen='Q12'),
    'erom_q0001e_mean' = list(dh_nhd='qa_ma', elfgen='MAF')
  )
  if (varkey == FALSE) {
    # they want it all
    return(nhd_map)
  }
  if (dataset == 'dh_nhd') {
   nhd_col <- as.character(nhd_map[[varkey]]$dh_nhd)
  } else {
    nhd_col <- as.charcter(nhd_map[[varkey]]$elfgen)
  }
  return(nhd_col)
}

elfgen_feature_nhdsegs <- function(
    hydroid
) {
  #x.metric <- 'erom_q0001e_mean'
  #y.metric <- 'aqbio_nt_total'
  #y.sampres <- 'species'
  
  #Determine watershed outlet nhd+ segment and hydroid
  riverseg_feature <- RomFeature$new(ds, list(hydroid=as.integer(hydroid)), TRUE)
  contained_df <- riverseg_feature$find_spatial_relations(
    target_entity = 'dh_feature', 
    inputs = list(
      bundle = 'watershed',
      ftype = 'nhdplus'
    ),
    operator = 'st_contains',
    return_geoms = FALSE,
    query_remote = TRUE
  )
  nhdplus_df <- as.data.frame(get_nhdplus(comid= contained_df$hydrocode))
  message(paste("length(nhdplus_df): ", length(nhdplus_df[,1])))
  return(nhdplus_df)
}

dh_elfdata <- function(watershed_feature, ws_varkey, bio_varkey, ds) {
  # elfdata_vahydro() function for retrieving data from VAHydro
  
  
  sql <- "
  select event.tid,to_timestamp(event.tstime), pv.varkey,
  biodat.propcode, biodat.propvalue as y_metric, dap.propvalue as x_metric,
    CASE 
      WHEN ws.ftype = 'nhd_huc8' THEN REPLACE(ws.hydrocode,'nhd_huc8_','') 
      WHEN ws.ftype = 'nhd_huc12' THEN REPLACE(ws.hydrocode,'huc12_', '') 
      WHEN ws.ftype = 'vahydro' THEN REPLACE(ws.hydrocode,'vahydrosw_wshed_','') 
      ELSE ws.hydrocode
    END as hydrocode,
    q01.propvalue as qa_01,
    q02.propvalue as qa_02,
    q03.propvalue as qa_03,
    q04.propvalue as qa_04,
    q05.propvalue as qa_05,
    q06.propvalue as qa_06,
    q07.propvalue as qa_07,
    q08.propvalue as qa_08,
    q09.propvalue as qa_09,
    q10.propvalue as qa_10,
    q11.propvalue as qa_11,
    q12.propvalue as qa_12,
    qmaf.propvalue as qa_ma,
    st.hydroid as station_hydroid
  from dh_feature_fielded as cov
  left outer join dh_feature_fielded as ws
  on (
    st_contains(cov.dh_geofield_geom, ws.dh_geofield_geom)
  )
  left outer join dh_feature_fielded as st
  on (
    st_contains(ws.dh_geofield_geom, st.dh_geofield_geom)
  )
  left outer join dh_variabledefinition as tsv 
  on (tsv.varkey = 'aqbio_sample_event')
  left outer join dh_timeseries as event
  on (
    event.varid = tsv.hydroid
    and event.featureid = st.hydroid
  ) 
  left outer join dh_properties as biodat
  on (
    biodat.featureid = event.tid
    and biodat.entity_type = 'dh_timeseries'
  ) 
  left outer join dh_variabledefinition as pv 
  on (
    pv.varkey = '[bio_varkey]'
    and pv.hydroid = biodat.varid
  )
  left outer join dh_variabledefinition as dav 
  on (
    dav.varkey = '[ws_varkey]'
  )
  left outer join dh_properties_fielded as dap 
  on (
    dap.featureid = ws.hydroid
    and dap.entity_type = 'dh_feature'
    and dap.varkey = '[ws_varkey]'
  )
  left outer join dh_properties_fielded as q01 
  on (
    q01.featureid = ws.hydroid
    and q01.entity_type = 'dh_feature'
    and q01.varkey = 'erom_q0001e_jan'
  )
  left outer join dh_properties_fielded as q02 
  on (
    q02.featureid = ws.hydroid
    and q02.entity_type = 'dh_feature'
    and q02.varkey = 'erom_q0001e_feb'
  )
  left outer join dh_properties_fielded as q03 
  on (
    q03.featureid = ws.hydroid
    and q03.entity_type = 'dh_feature'
    and q03.varkey = 'erom_q0001e_mar'
  )
  left outer join dh_properties_fielded as q04
  on (
    q04.featureid = ws.hydroid
    and q04.entity_type = 'dh_feature'
    and q04.varkey = 'erom_q0001e_apr'
  )
  left outer join dh_properties_fielded as q05 
  on (
    q05.featureid = ws.hydroid
    and q05.entity_type = 'dh_feature'
    and q05.varkey = 'erom_q0001e_may'
  )
  left outer join dh_properties_fielded as q06 
  on (
    q06.featureid = ws.hydroid
    and q06.entity_type = 'dh_feature'
    and q06.varkey = 'erom_q0001e_june'
  )
  left outer join dh_properties_fielded as q07
  on (
    q07.featureid = ws.hydroid
    and q07.entity_type = 'dh_feature'
    and q07.varkey = 'erom_q0001e_july'
  )
  left outer join dh_properties_fielded as q08 
  on (
    q08.featureid = ws.hydroid
    and q08.entity_type = 'dh_feature'
    and q08.varkey = 'erom_q0001e_aug'
  )
  left outer join dh_properties_fielded as q09 
  on (
    q09.featureid = ws.hydroid
    and q09.entity_type = 'dh_feature'
    and q09.varkey = 'erom_q0001e_sept'
  )
  left outer join dh_properties_fielded as q10 
  on (
    q10.featureid = ws.hydroid
    and q10.entity_type = 'dh_feature'
    and q10.varkey = 'erom_q0001e_oct'
  )
  left outer join dh_properties_fielded as q11 
  on (
    q11.featureid = ws.hydroid
    and q11.entity_type = 'dh_feature'
    and q11.varkey = 'erom_q0001e_jan'
  )
  left outer join dh_properties_fielded as q12 
  on (
    q12.featureid = ws.hydroid
    and q12.entity_type = 'dh_feature'
    and q12.varkey = 'erom_q0001e_dec'
  )
  left outer join dh_properties_fielded as qmaf 
  on (
    qmaf.featureid = ws.hydroid
    and qmaf.entity_type = 'dh_feature'
    and qmaf.varkey = 'erom_q0001e_mean'
  )
  left outer join dh_variabledefinition as srv 
  on (
    srv.varkey = 'sampres'
  )
  left outer join dh_properties as sr 
  on (
    sr.featureid = event.tid
    and sr.entity_type = 'dh_timeseries'
    and srv.hydroid = sr.varid
  )
  where pv.hydroid is not null
  and sr.propcode = '[sampres]'
  and cov.hydroid = [covid] 
  and ws.ftype = '[ws_ftype]'
  and ws.bundle='watershed';
" 
  config <- list(
    covid = watershed_feature$hydroid,
    ws_ftype = 'nhdplus',
    ws_varkey = ws_varkey,
    bio_varkey = bio_varkey,
    sampres = 'species'
  )
  sql <- str_replace_all(sql, '\\[covid\\]', as.character(config$covid))
  sql <- str_replace_all(sql, '\\[ws_ftype\\]', as.character(config$ws_ftype))
  sql <- str_replace_all(sql, '\\[ws_varkey\\]', as.character(config$ws_varkey))
  sql <- str_replace_all(sql, '\\[bio_varkey\\]', as.character(config$bio_varkey))
  sql <- str_replace_all(sql, '\\[sampres\\]', as.character(config$sampres))
  message(paste("querying for samples contained by", watershed_feature$ftype, watershed_feature$hydrocode))
  watershed_df <- sqldf(sql, conn=ds$connection)
  return(watershed_df)
}

elfgen_huc <- function(
  runid, hydroid, huc_level, dataset, scenprop, ds,
  ws_varkey = 'erom_q0001e_mean',
  bio_varkey = 'aqbio_nt_total',
  save_directory = '/var/www/html/data/proj3/out',
  save_url = 'http://deq1.bse.vt.edu:81/data/proj3/out',
  site = 'http://deq1.bse.vt.edu/d.dh',
  quantile = 0.8,
  breakpt = 530,
  yaxis_thresh = 53
  ) {
  #x.metric <- 'erom_q0001e_mean'
  #y.metric <- 'aqbio_nt_total'
  #y.sampres <- 'species'

  pdf(file = NULL) # disable pdf image writing
  #Determine watershed outlet nhd+ segment and hydroid
  
  nhdplus_df <- elfgen_feature_nhdsegs(hydroid)
  nhd_col <- elfgen_varkey_nhd_col(ws_varkey)
  nhdplus_df <- nhdplus_df[,c("comid", "gnis_name", "reachcode", "totdasqkm", nhd_col)]
  
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
  outlet_nhdplus_segment <-sqldf("select * from nhdplus_df ORDER BY totdasqkm DESC LIMIT 1")
  #Pulls mean annual outlet flow
  outlet_flow <- outlet_nhdplus_segment[,nhd_col] #outlet flow as erom_q0001e_mean of nhdplus segment
  nhd_code <- substr(outlet_nhdplus_segment$reachcode, 1, as.integer(str_remove(huc_level, "huc")))
  code_out <- outlet_nhdplus_segment$comid
  rseg.name <- outlet_nhdplus_segment$gnis_name


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

  #HUC Section---------------------------------------------------------------------------
  watershed.code <- as.character(nhd_code)
  watershed.bundle <- 'watershed'
  watershed.ftype <- paste("nhd_", huc_level, sep = "") #watershed.ftpe[i] when creating function
  watershed_feature = RomFeature$new(ds, list(ftype=watershed.ftype, hydrocode=watershed.code), TRUE)
  datasite <- site

  if (dataset == 'IchthyMaps'){
    #if loop below works only for huc:6,8,10 due to naming convention in containing_watershed
    if(huc_level == 'huc8'){
      watershed.code <- str_sub(watershed.code, -8,-1)
      }
    watershed.df <- elfdata(watershed.code)
  }else{
    watershed.df <- dh_elfdata(watershed_feature, ws_varkey, bio_varkey, ds)
    # this may not be necessary but we do it to insure extra cols don't cause trouble
    watershed.df <- watershed.df[,c('x_metric', 'y_metric', 'hydrocode')]
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
  
  
  confidence <- elfgen_confidence(elf,rseg.name, outlet_flow,yaxis_thresh,cuf, nhd_col)

  
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
