source("/var/www/R/config.R")

elid = 210901 # 212263 | Appomattox River below Brasfield Dam
elsql = paste0(
  "select featureid from dh_properties where propname = 'om_element_connection' 
   and propvalue = ", elid)
eldf = sqldf(elsql,connection = ds$connection)
pid = as.integer(eldf$featureid[1])
om_el_url = paste0(omsite, "/om/get_model.php?elementid=", elid)
om_json = ds$auth_read(om_el_url, content_type ="application/json")
dh_json = ds$get_json_prop(pid)

om_migrate_check_migrated <- function (dh_json, om_json, skippable_classes = FALSE) {
  if (is.logical(skippable_classes)) {
    skippable_classes = c('queryWizardComponent')
  }
  dh_props = names(dh_json[[names(dh_json)]])
  om_props = names(om_json$processors)
  common_props = intersect(om_props, dh_props)
  dh_missing = om_props[!(om_props %in% common_props)]
  migration_status = list(
    status = TRUE,
    missing_components = as.list(dh_missing),
    mandatory_components = list()
  )
  # we iterate through to check if a missing component *should* be migrated
  for (i in dh_missing) {
    object_class = om_json$processors[[i]]$object_class
    if (object_class %in% skippable_classes) {
      message(paste("Element", om_json$name, ": Skipping", i, ", un-needed object_class", object_class))
    } else {
      migration_status$status = FALSE
      migration_status$mandatory_components[[i]] = i
    }
  }
  message(paste("Critical component missing", i))
  return(migration_status)
}
# does not yet work:
# om_migrate_check_migrated(dh_json, om_json )

# Find the matching model
if (!is.logical(pid)) {
  dh_model = RomProperty$new(ds,list(pid=pid), TRUE)
}

# If no match, user must have supplied a parent entity_type/entity_id to create a model below
# Can we simply iterate through the tree of properties and create them?
# like a reverse of the model to json method?

# Get all plugins so that we can ry to find a good match
om_all = getNamespaceExports("hydrotools")
om_R6 = om_all[substr(om_all,1,2) == "dH"]
om_classes = list()
for (i in om_R6) {
  # 
  plugin <- eval(parse(text = paste0(i,'$new()')))
  om_classes[[i]] = i
}
for (prop in om_json$processors) {
  if (!is.null(prop$object_class)) {
    message(prop$name)
    if (prop$object_class == 'dataMatrix') {
      matrix_rowcol = data.frame()
      if (length(prop$matrix_rowcol) > 0) {
        h = length(prop$matrix_rowcol)
        w = length(prop$matrix_rowcol[[1]])
        for (i in 1:h) {
          r = c()
          for (j in 1:w) {
            r[j] = prop$matrix_rowcol[[i]][[j]]
          }
          matrix_rowcol = rbind(matrix_rowcol, r)
        }
      }
      dh_matrix = dh_model$set_prop(prop$name, data_matrix = t(matrix_rowcol))
      matrix_props = c('valuetype', 'lutype1', 'keycol1', 'lutype2', 'keycol2', 'value_dbcolumntype', 'eval_type')
      dh_matrix$set_prop('valuetype', prop$value_type)
      dh_matrix$set_prop('lutype1', prop$lutype1)
      dh_matrix$set_prop('keycol1', propcode=prop$keycol1)
      dh_matrix$set_prop('lutype2', prop$lutype2)
      dh_matrix$set_prop('keycol2', propcode=prop$keycol2)
      dh_matrix$set_prop('value_dbcolumntype', propcode=prop$value_dbcolumntype)
      dh_matrix$set_prop('eval_type', propcode=prop$eval_type)
    }
  }
}

# 