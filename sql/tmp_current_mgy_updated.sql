create temp table tmp_current_mgy_updated as (
  select fac.hydroid, 
    fac.ftype,
    model.propname, 
    max(fac_wd.propvalue) as fac_current_mgy,
    sum(mp_wd.propvalue) as current_mgy,
    array_accum(mp.hydroid) as mp_list,
    rseg.hydrocode as rseg_hydrocode,
    substr(rseg.hydrocode, 17) as riverseg,
    model_rseg.propcode as model_rseg,
    model.pid as model_pid
  from dh_feature as fac 
  left outer join field_data_dh_link_facility_mps as lnk
  on (
    lnk.entity_type = 'dh_feature'
    and lnk.dh_link_facility_mps_target_id = fac.hydroid
  )
  left outer join dh_feature as mp 
  on (mp.hydroid = lnk.entity_id)
  left outer join field_data_dh_geofield as mpg 
  on (
    mpg.entity_type = 'dh_feature'
    and mpg.entity_id = mp.hydroid
  )
  left outer join field_data_dh_geofield as rg
  on (
    rg.entity_type = 'dh_feature'
    and rg.bundle = 'watershed'
    and st_contains(
      st_setsrid(rg.dh_geofield_geom,4326), st_setsrid(mpg.dh_geofield_geom,4326) 
    )
  ) 
  left outer join dh_feature as rseg 
  on (rseg.hydroid = rg.entity_id)
  -- now get the data 
  left outer join dh_properties as fac_wd 
  on (
    fac_wd.entity_type = 'dh_feature'
    and fac_wd.featureid = fac.hydroid
    and fac_wd.propname = 'wd_current_mgy'
  )
  left outer join dh_properties as mp_wd 
  on (
    mp_wd.entity_type = 'dh_feature'
    and mp_wd.featureid = mp.hydroid
    and mp_wd.propname = 'wd_current_mgy'
  )
  -- now the model
  left outer join dh_properties as model 
  on (
    model.entity_type = 'dh_feature'
    and model.featureid = fac.hydroid
    and model.propcode = 'vahydro-1.0'
  )
  left outer join dh_properties as model_rseg
  on (
    model_rseg.entity_type = 'dh_properties'
    and model_rseg.featureid = model.pid
    and model_rseg.propname = 'riverseg'
  )
  where rseg.hydroid is not null
    and rseg.ftype = 'vahydro'
  -- this must be here or we won't get valid results
  and model_rseg.propcode = substr(rseg.hydrocode, 17) 
  -- limit to a single facility for testing
  --  and fac.hydroid = 71956 
    and mp.bundle = 'intake'
  group by rseg.hydrocode,
    substr(rseg.hydrocode, 17), model.pid, fac.hydroid, model_rseg.propcode, model.propname, fac.ftype
);


create or replace temp view tmp_current_mgy_gw as (
  select fac.hydroid, 
    fac.ftype,
    model.propname, 
    max(fac_wd.propvalue) as fac_current_mgy,
    sum(mp_wd.propvalue) as current_mgy,
    array_accum(mp.hydroid) as mp_list,
    rseg.hydrocode as rseg_hydrocode,
    substr(rseg.hydrocode, 17) as riverseg,
    model_rseg.propcode as model_rseg,
    model.pid as model_pid
  from dh_feature as fac 
  left outer join field_data_dh_link_facility_mps as lnk
  on (
    lnk.entity_type = 'dh_feature'
    and lnk.dh_link_facility_mps_target_id = fac.hydroid
  )
  left outer join dh_feature as mp 
  on (mp.hydroid = lnk.entity_id)
  left outer join field_data_dh_geofield as mpg 
  on (
    mpg.entity_type = 'dh_feature'
    and mpg.entity_id = mp.hydroid
  )
  left outer join field_data_dh_geofield as rg
  on (
    rg.entity_type = 'dh_feature'
    and rg.bundle = 'watershed'
    and st_contains(
      st_setsrid(rg.dh_geofield_geom,4326), st_setsrid(mpg.dh_geofield_geom,4326) 
    )
  ) 
  left outer join dh_feature as rseg 
  on (rseg.hydroid = rg.entity_id)
  -- now get the data 
  left outer join dh_properties as fac_wd 
  on (
    fac_wd.entity_type = 'dh_feature'
    and fac_wd.featureid = fac.hydroid
    and fac_wd.propname = 'wd_current_mgy'
  )
  left outer join dh_properties as mp_wd 
  on (
    mp_wd.entity_type = 'dh_feature'
    and mp_wd.featureid = mp.hydroid
    and mp_wd.propname = 'wd_current_mgy'
  )
  -- now the model
  left outer join dh_properties as model 
  on (
    model.entity_type = 'dh_feature'
    and model.featureid = fac.hydroid
    and model.propcode = 'vahydro-1.0'
  )
  left outer join dh_properties as model_rseg
  on (
    model_rseg.entity_type = 'dh_properties'
    and model_rseg.featureid = model.pid
    and model_rseg.propname = 'riverseg'
  )
  where rseg.hydroid is not null
    and rseg.ftype = 'vahydro'
  -- this must be here or we won't get valid results
  and model_rseg.propcode = substr(rseg.hydrocode, 17) 
  -- limit to a single facility for testing
  --  and fac.hydroid = 71956 
    and mp.bundle = 'well'
    and mp.ftype NOT LIKE 'gw2%'
  group by rseg.hydrocode,
    substr(rseg.hydrocode, 17), model.pid, fac.hydroid, model_rseg.propcode, model.propname, fac.ftype
);

