# synchronize new school with old school db
options(scipen=999)
basepath='/var/www/R';
source("/var/www/R/config.R")
source("https://raw.githubusercontent.com/HARPgroup/hydro-tools/master/R/fac_utils.R")


argst <- commandArgs(trailingOnly=T)
pid <- as.integer(argst[1]) # Ex: pid=7693370
if (length(argst) > 1) {
  elid <- as.integer(argst[2])  
  q = paste0(
    "select featureid from dh_properties where propname = 'om_element_connection' 
   and propvalue = ", elid)
  message(q)
  pid = as.integer(sqldf(q,
    connection = ds$connection)$featureid[1]
  )
}

element_list <- ds$get_json_prop(pid)
elid <- element_list$om_element_connection$value
for (i in names(element_list)) {
  if (substr(i,1,6) == 'runid_') {
    element_list[[i]] <- NULL
  }
}
element_json <- jsonlite::prettify(jsonlite::toJSON(element_list), auto_unbox = TRUE)
#element_json <- paste0('"',stringr::str_replace_all(jsonlite::toJSON(element_list),'"','\"'),'"')
element_path <- paste0("element_", elid,".json")
write(element_json,element_path)
if (!is.na(elid)) {
  cmd_str <- paste("php import_element_json.php", elid, element_path )
  system(cmd_str)
}