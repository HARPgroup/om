# TEST 1 (has enough data)
basepath='/var/www/R';
source("/var/www/R/config.R")
source(paste(om_location,'R/summarize','rseg_elfgen.R',sep='/'))

ds <- RomDataSource$new(site, rest_uname = rest_uname)
ds$get_token(rest_pw)

model_scenario <- RomProperty$new(
  ds,
  list(
    featureid=6993101, 
    entity_type="dh_properties", 
    propcode="hsp2_2022"
  ), 
  TRUE
)

scenario_name <- "hsp2_2022"
rseg_hydroid <- 605598
huc_level <- "huc8"
Dataset <- "VAHydro-EDAS"
# model_scenario <- 
# ds <- 
# image_dir <- "/media/model/p6/out/river/hsp2_2022/images/"
image_dir <-"C:/Users/nrf46657/Desktop/GitHub/om/R/summarize/tmp/"
# save_url <- "http://deq1.bse.vt.edu:81/p6/out/river/hsp2_2022/images"
save_url <- image_dir
site <- "http://deq1.bse.vt.edu/d.dh"


elfgen_result <- purrr::safely(
  elfgen_huc(
    runid = scenario_name,
    hydroid = rseg_hydroid,
    huc_level = huc_level,
    dataset = Dataset,
    scenprop = model_scenario,
    ds = ds,
    save_directory = image_dir,
    save_url = save_url,
    site = site
  )
)


runid = scenario_name
hydroid = rseg_hydroid
huc_level = huc_level
dataset = Dataset
scenprop = model_scenario
ds = ds
save_directory = image_dir
save_url = save_url
site = site


################################################################
################################################################
# TEST 2 (does not have enough data)
basepath='/var/www/R';
source("/var/www/R/config.R")
source(paste(om_location,'R/summarize','rseg_elfgen.R',sep='/'))

ds <- RomDataSource$new(site, rest_uname = rest_uname)
ds$get_token(rest_pw)

model_scenario <- RomProperty$new(
  ds,
  list(
    featureid=7019952, 
    entity_type="dh_properties", 
    propcode="hsp2_2022"
  ), 
  TRUE
)

scenario_name <- "hsp2_2022"
rseg_hydroid <- 605512
huc_level <- "huc8"
Dataset <- "VAHydro-EDAS"
image_dir <-"C:/Users/nrf46657/Desktop/GitHub/om/R/summarize/tmp/"
save_url <- image_dir
site <- "http://deq1.bse.vt.edu/d.dh"

elfgen_huc(
  runid = scenario_name,
  hydroid = rseg_hydroid,
  huc_level = huc_level,
  dataset = Dataset,
  scenprop = model_scenario,
  ds = ds,
  save_directory = image_dir,
  save_url = save_url,
  site = site
)


################################################################
################################################################
# TEST 3 (no nhdplus found)
basepath='/var/www/R';
source("/var/www/R/config.R")
source(paste(om_location,'R/summarize','rseg_elfgen.R',sep='/'))

ds <- RomDataSource$new(site, rest_uname = rest_uname)
ds$get_token(rest_pw)

model_scenario <- RomProperty$new(
  ds,
  list(
    featureid=7019886, 
    entity_type="dh_properties", 
    propcode="hsp2_2022"
  ), 
  TRUE
)

scenario_name <- "hsp2_2022"
rseg_hydroid <- 605610
huc_level <- "huc8"
Dataset <- "VAHydro-EDAS"
image_dir <-"C:/Users/nrf46657/Desktop/GitHub/om/R/summarize/tmp/"
save_url <- image_dir
site <- "http://deq1.bse.vt.edu/d.dh"

elfgen_huc(
  runid = scenario_name,
  hydroid = rseg_hydroid,
  huc_level = huc_level,
  dataset = Dataset,
  scenprop = model_scenario,
  ds = ds,
  save_directory = image_dir,
  save_url = save_url,
  site = site
)

# 
# runid = scenario_name
# hydroid = rseg_hydroid
# huc_level = huc_level
# dataset = Dataset
# scenprop = model_scenario
# ds = ds
# save_directory = image_dir
# save_url = save_url
# site = site