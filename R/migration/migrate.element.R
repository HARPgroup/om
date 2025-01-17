source("/var/www/R/config.R")
omds <- RomDataSource$new(site, rest_uname = odbc_uname, connection_type = 'odbc', dbname = 'model')

elid = 210901 # 212263 | Appomattox River below Brasfield Dam
elsql = paste0(
  "select featureid from dh_properties where propname = 'om_element_connection' 
   and propvalue = ", elid)
eldf = sqldf(elsql,connection = ds$connection)
pid = as.integer(eldf$featureid[1])
om_el_url = paste0(omsite, "/om/get_model.php?elementid=", elid)
el_json = ds$auth_read(om_el_url, content_type ="application/json")
dh_json = ds$get_json_prop(pid)

om_migrate_check_migrated <- function (dh_json, om_json, skippable_classes = FALSE) {
  if (is.logical(skippable_classes)) {
    skippable_classes = c('queryWizardComponent')
  }
  dh_props = names(dh_json[[names(dh_json)]])
  om_props = names(el_json$processors)
  common_props = intersect(om_props, dh_props)
  dh_missing = om_props[!(om_props %in% common_props)]
  migration_status = list(
    status = TRUE,
    missing_components = as.list(dh_missing),
    mandatory_components = list()
  )
  # we iterate through to check if a missing component *should* be migrated
  for (i in dh_missing) {
    object_class = el_json$processors[[i]]$object_class
    if (object_class %in% skippable_classes) {
      message(paste("Element", el_json$name, ": Skipping", i, ", un-needed object_class", object_class))
    } else {
      migration_status$status = FALSE
      migration_status$mandatory_components[[i]] = i
    }
  }
  message(paste("Critical component missing", i))
  return(migration_status)
}
