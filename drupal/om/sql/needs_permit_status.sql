copy (
  select pid, 'permit_status' as propname, 'dh_feature' as entity_type, 'wsp_system_type' as propname, 
    'om_class_AlphanumericConstant' as varkey, 
    CASE 
      WHEN model_permit_status is not null then model_permit_status
      ELSE pstatus_vwp_exempt_mgd 
    END as propcode 
  from (
    select pstatus as pstatus_vwp_exempt_mgd, mod.featureid, mod.pid, 
      vwe.featureid as model, fac.hydroid,
      max(perm.adminid) as vwp_id, 
      CASE 
        WHEN max(perm.adminid) is not null THEN 'active' 
        ELSE pstatus 
      END as permit_status,
      mps.propcode as model_permit_status
    from 
    (
      select entity_type, 
      featureid,
      case 
        when propcode like 'wsp%' then 'npne' 
        WHEN propcode like 'vwp%' THEN 'active' 
        ELSE 'exempt' 
      END as pstatus 
      from dh_properties 
      where propname = 'vwp_exempt_mgd' 
      and entity_type = 'dh_properties'
    ) as vwe
    left outer join dh_properties as mod
    on (
      mod.pid = vwe.featureid 
      and vwe.entity_type = 'dh_properties'
    )
    left outer join dh_feature as fac 
    on (
      fac.hydroid = mod.featureid 
      and mod.entity_type = 'dh_feature'
    )
    left outer join field_data_dh_link_admin_location as pl
    on (
      pl.entity_id = fac.hydroid 
    )
    left outer join dh_adminreg_feature as perm
    on (
      perm.adminid = pl.dh_link_admin_location_target_id
      and perm.ftype = 'vwp'
    )
    left outer join dh_properties as mps
    on (
      mod.pid = mps.featureid 
      and mps.entity_type = 'dh_properties'
      and mps.propname = 'permit_status'
    )
    -- where perm.adminid is not null 
    group by pstatus, mod.featureid, vwe.featureid, fac.hydroid, mps.propcode,  mod.pid
  ) as foo 
  -- only do for those that do not have a setting
  -- even if the setting that they have is wrong, i.e., in the case of VWPs
  -- because these need their models to be fully populated.
  WHERE model_permit_status IS NULL
) to '/tmp/need_permit_status.txt' WITH HEADER CSV DELIMITER E'\t';

