source("/var/www/R/config.R")

elid = 210901 # 212263 | Appomattox River below Brasfield Dam
model = ModelElementBase$new(ds,list(elementid=elid), ds_om = ds_model)
model$load_json_model()
om_el_url = paste0(omsite, "/om/get_model.php?elementid=", model$elementid)
el_json = ds$auth_read(om_el_url, content_type ="application/json")
dh_json = model$json

om_migrate_check_migrated <- function (dh_json, el_json, skippable_classes = FALSE) {
  if (is.logical(skippable_classes)) {
    skippable_classes = c('queryWizardComponent')
  }
  dh_props = names(dh_json)
  om_props = names(el_json$processors)
  common_props = intersect(om_props, dh_props)
  dh_missing = om_props[!(om_props %in% common_props)]
  migration_status = list(
    status = TRUE,
    missing_processors = as.list(dh_missing),
    mandatory_processors = list()
  )
  # we iterate through to check if a missing component *should* be migrated
  for (i in dh_missing) {
    object_class = el_json$processors[[i]]$object_class
    if (object_class %in% skippable_classes) {
      message(paste("Element", el_json$name, ": Skipping", i, ", un-needed object_class", object_class))
    } else {
      migration_status$status = FALSE
      migration_status$mandatory_processors[[i]] = i
    }
  }
  message(paste("Critical component missing", i))
  return(migration_status)
}

model_mstatus = om_migrate_check_migrated( dh_json = model$json, el_json = el_json, FALSE)
model_mstatus$missing_processors

#### Migrate from OM to dH ####
for (i in model_mstatus$missing_processors) {
  om_comp = el_json$processors[[i]]
  p_plugin = NA
  # model$prop$set_prop(i, om_comp)
  if (om_comp[['object_class']] == 'dataMatrix') {
    p_plugin = dHOMDataMatrix$new()
  } else if (om_comp[['object_class']] == 'Equation') {
    p_plugin = dHOMEquation$new()
  } else if (om_comp[['object_class']] == 'wsp_flowby') {
    p_plugin = dHOMWaterSystemFlowBy$new()
  } else if (om_comp[['object_class']] == 'wsp_1tierflowby') {
    p_plugin = dHOMWaterSystemTieredFlowBy$new()
  }
  if (typeof(p_plugin) == 'environment') {
    prop_list = p_plugin$fromOM(om_comp)
    message(prop_list)
    dmj = NULL
    if (!is.null(prop_list$data_matrix)) {
      dmj = jsonlite::fromJSON(prop_list$data_matrix)
    }
    dh_prop = model$prop$set_prop(
      propname = prop_list$propname, propvalue = prop_list$propvalue, 
      propcode = prop_list$propcode, data_matrix = dmj
    )
  }
}
