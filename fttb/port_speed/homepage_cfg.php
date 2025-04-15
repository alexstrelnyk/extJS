<?
//error_reporting(E_ALL);
$hpv = array(
   'locd' => array(
      'psql' => "
	    	select l.*,
	      lt.name location_type,
	      pl.name parent_location,
	      du.name create_user,
	      dum.name modified_user,
	      ps.name PROVISIONSTATUS,
	      nvl2(lt.tablename,lt.tablename||'_O',null) sattab
	  from location_o l
	  join locationtype lt on l.LOCATION2LOCATIONTYPE = lt.LOCATIONTYPEID
	  left join LOCATION_o pl on l.LOCATION2PARENTLOCATION = pl.LOCATIONID
	  left join DIMUSER du on l.CREATEDBY2DIMUSER = du.DIMUSERID
	  left join DIMUSER dum on l.LASTMODIFIEDBY2DIMUSER = dum.DIMUSERID
	  join PROVISIONSTATUS ps on l.location2PROVISIONSTATUS = ps.PROVISIONSTATUSID
	  where l.LOCATIONID = :id",
      'sattab' => array(
         'sql' => "select * from :table where locationid = :id"
      ),
      'links' => array(
         'PARENT_LOCATION' => array('id' => 'LOCATION2PARENTLOCATION', 'prefix' => 'locd')
      ),
      'switch_sql' => "select case when location2locationtype in (1900000001) then 'LOCATION_SITE'
                     when location2locationtype in (1900000004) then 'LOCATION_TKD'
                     when location2locationtype in (1900000007) then 'LOCATION_BEE'
                     when location2locationtype in (1900000006) then 'LOCATION_PFJ'
                     when location2locationtype in (1900000011) then 'LOCATION_HOUSE' end template
                     from location_o
                     where locationid = :id",
      'templates' => array(
         'LOCATION_SITE' => array(
            'sql' => "select l.locationid, l.name, l.fullname, l.relativename, l.alias1,
           l.alias2, 
           substr(l.objectid, 0,6) pm_site, l.objectid, 
           l.subtype, l.substatus, l.description,
           l.address, l.towncity, l.province, l.zip, l.responsible,
           l.telephone, l.fax, l.notes, l.createddate,
           l.lastmodifieddate, l.physicalx, l.physicaly, l.location2parentlocation,
           decode(l.markedfordelete, 1, 'Yes', 'No') as markedfordelete,
        lt.name location_type, pl.name parent_location,
        du.name create_user, dum.name modified_user, 
		decode(ps.name, 'Not in use', '<font color=''red''><b>Not in use</font></b>', ps.name) PROVISIONSTATUS,
        e1.value as category_, e2.value as group_, e3.value as company_,  e4.value as zone_,
        e5.value as manual_priority, e6.value as ac_, e7.value as site_type,
        sat.priority, sat.nmin, sat.nrp, sat.min, sat.pop, sat.pop,
        sat.agg, sat.msc_bsc, sat.rbs, sat.atoll_site_name, sat.pop, sat.wmb, decode(sat.saturn, 1, 'Saturn', 'No') saturn,
        pl.province obl,
        pm.notice_descr,
        pm.client_name_descr,
        pm.wo_num_descr,
        e8.value DGA,
        e9.value PPO,
        e10.value AVR,
        e11.value OZ,
        e12.value OZ_E,
        e13.value OPS,
        e17.value WAR_ZONE,
        e18.value AVR2,

--         replace(regexp_substr(l.physicalx,'\d{1,3}') + 
--         regexp_substr(l.physicalx,'\d{1,3}',3)/60 +
--         (regexp_substr(l.physicalx,'\d{1,3}',6)||'.'||regexp_substr(l.physicalx,'\d{1,3}',9))/3600,',','.') google_px,
--         replace(regexp_substr(l.physicaly,'\d{1,3}') + 
--         regexp_substr(l.physicaly,'\d{1,3}',3)/60 +
--         (regexp_substr(l.physicaly,'\d{1,3}',6)||'.'||regexp_substr(l.physicaly,'\d{1,3}',9))/3600,',','.') google_py,
replace(ks_gis.SECtoG(l.physicalx),',','.') as google_px,
replace(ks_gis.SECtoG(l.physicaly),',','.') as google_py,

         e14.value SOCKETOUT,
         e15.value THIRD_PARTY_METTER,
         decode(sat.STATE_3G, 100, 'On Air', 90, 'DUW connected', null) STATE_3G,
		 e16.value wp,
		 sat.sp,
	nvl(tmp.tempout,'No') as tempout
    from location_o l
    join locationtype lt on l.LOCATION2LOCATIONTYPE = lt.LOCATIONTYPEID
    left join LOCATION_o pl on l.LOCATION2PARENTLOCATION = pl.LOCATIONID
    left join DIMUSER du on l.CREATEDBY2DIMUSER = du.DIMUSERID
    left join DIMUSER dum on l.LASTMODIFIEDBY2DIMUSER = dum.DIMUSERID
    join PROVISIONSTATUS ps on l.location2PROVISIONSTATUS = ps.PROVISIONSTATUSID
    join sattab_locationsite_o sat on sat.locationid = l.locationid
--    left join PLMON.PM_CRAMER_POP_WMB_DESCR@PLANMONITOR_KS pm on l.objectid=pm.site_name||';'||pm.location_name
    left join KS_PLANMONITOR_LOCATION pm on l.objectid=pm.site_name||';'||pm.location_name
    left join enumeration e1 on e1.TABLENAME = 'SATTAB_LOCATIONSITE'
                                              and e1.FIELDNAME = 'CATEGORY_'
                                              and e1.SEQUENCE = sat.category_
    left join enumeration e2 on e2.TABLENAME = 'SATTAB_LOCATIONSITE'
                                              and e2.FIELDNAME = 'GROUP_'
                                              and e2.SEQUENCE = sat.group_
    left join enumeration e3 on e3.TABLENAME = 'SATTAB_LOCATIONSITE'
                                              and e3.FIELDNAME = 'COMPANY_'
                                              and e3.SEQUENCE = sat.company_
    left join enumeration e4 on e4.TABLENAME = 'SATTAB_LOCATIONSITE'
                                              and e4.FIELDNAME = 'ZONE'
                                              and e4.SEQUENCE = sat.zone
    left join enumeration e5 on e5.TABLENAME = 'SATTAB_LOCATIONSITE'
                                              and e5.FIELDNAME = 'MANUAL_PRIORITY'
                                              and e5.SEQUENCE = sat.manual_priority
   left join enumeration e6 on e6.TABLENAME = 'SATTAB_LOCATIONSITE'
                                              and e6.FIELDNAME = 'AC_MORE_1000V'
                                              and e6.SEQUENCE = sat.ac_more_1000v
   left join enumeration e7 on e7.TABLENAME = 'SATTAB_LOCATIONSITE'
                                              and e7.FIELDNAME = 'SITE_TYPE'
                                              and e7.SEQUENCE = sat.site_type   
   left join  enumeration e8 on e8.TABLENAME = 'SATTAB_LOCATIONSITE' and e8.FIELDNAME = 'DGA'  and e8.SEQUENCE = sat.dga
   left join  enumeration e9 on e9.TABLENAME = 'SATTAB_LOCATIONSITE' and e9.FIELDNAME = 'PPO'  and e9.SEQUENCE = sat.ppo
   left join  enumeration e10 on e10.TABLENAME = 'SATTAB_LOCATIONSITE' and e10.FIELDNAME = 'AVR'  and e10.SEQUENCE = sat.avr
   left join  enumeration e11 on e11.TABLENAME = 'SATTAB_LOCATIONSITE' and e11.FIELDNAME = 'OZ'  and e11.SEQUENCE = sat.oz 
   left join enumeration e12 on e12.TABLENAME = 'SATTAB_LOCATIONSITE' and e12.FIELDNAME = 'OZ_E' and e12.SEQUENCE = sat.oz_e
   left join  enumeration e13 on e13.TABLENAME = 'SATTAB_LOCATIONSITE' and e13.FIELDNAME = 'SIGNALIZATION' and e13.SEQUENCE = sat.SIGNALIZATION
   left join  enumeration e14 on e14.TABLENAME = 'SATTAB_LOCATIONSITE' and e14.FIELDNAME = 'SOCKETOUT' and e14.SEQUENCE = sat.socketout
   left join  enumeration e15 on e15.TABLENAME = 'SATTAB_LOCATIONSITE' and e15.FIELDNAME = 'THIRD_PARTY_METTER' and e15.SEQUENCE = sat.THIRD_PARTY_METTER
   left join  enumeration e16 on e16.TABLENAME = 'SATTAB_LOCATIONSITE' and e16.FIELDNAME = 'WP' and e16.SEQUENCE = sat.wp
   left join  enumeration e17 on e17.TABLENAME = 'SATTAB_LOCATIONSITE' and e17.FIELDNAME = 'WAR_ZONE' and e17.SEQUENCE = sat.war_zone
   left join  enumeration e18 on e18.TABLENAME = 'SATTAB_LOCATIONSITE' and e18.FIELDNAME = 'AVR2' and e18.SEQUENCE = sat.avr2
   left join ks_tempout2 tmp on tmp.locationid=l.locationid
	  where l.LOCATIONID = :id",
            'file' => '../cfg/hp_templates/loc_site.html',
            'header' => '<link rel="stylesheet" type="text/css" href="../css/general.css">'
         ),
         'LOCATION_TKD' => array(
            'sql' => "select l.locationid, l.name, l.fullname, l.relativename, l.alias1,
           l.alias2, l.objectid, l.subtype, l.substatus, l.description,
           l.address, l.towncity, l.province, l.zip, l.responsible,
           l.telephone, l.fax, l.notes, l.createddate,
           l.lastmodifieddate, l.physicalx, l.physicaly, l.location2parentlocation,
		   vft.TT tt,
		   vft.KPI kpi,
        lt.name location_type, pl.name parent_location, nvl(pl.alias2,'FIX-TO-CO') workgroup,
        du.name create_user, dum.name modified_user, decode(l.markedfordelete, 1, 'Marked for deleted', ps.name) PROVISIONSTATUS,
           case when vp.vip_monitor=1 then 'VIP' else 'Standart' end as vip_monitor,
           vp.mdu_lot, tl.ttwoslink, pow.powerused,
           case when vp.ups=1 then vp.ups_type||' '||vp.ups_place||' owner:'||vp.ups_owner||' model:'||vp.ups_model||' installed:'||to_char(vp.ups_data_install,'dd.mm.yyyy')
                when vp.ups=2 then 'Removed to service'
                when vp.ups=3 then 'Removed'
            else 'Not installed' end as ups
    from location_o l
    join locationtype lt on l.LOCATION2LOCATIONTYPE = lt.LOCATIONTYPEID
    left join LOCATION_o pl on l.LOCATION2PARENTLOCATION = pl.LOCATIONID
    left join KS_VPORTAL_FTTB_TKD vp on vp.id_tkd=l.objectid
    left join DIMUSER du on l.CREATEDBY2DIMUSER = du.DIMUSERID
    left join DIMUSER dum on l.LASTMODIFIEDBY2DIMUSER = dum.DIMUSERID
    join PROVISIONSTATUS ps on l.location2PROVISIONSTATUS = ps.PROVISIONSTATUSID 
    left join v_fttb_powerused pow on pow.locationid=l.locationid
    left join V_FTTB_TKD_KPI30 vft on vft.locationid=l.locationid
    left join (select
l.loc_sourceid as locationid,
listagg('<a href=http://hpovsd1:8081/ttwos/ViewAttachFromWO.jsp?vWo='||w.wor_id||'>'||to_char(w.wor_actualfinish,'dd.mm.yyyy')||' WO '||w.wor_id||'</a>','<br>') WITHIN GROUP (order by w.wor_id) as ttwoslink


from itsd.sd_workorders@ttwos w
join ITSD.SD_WOR_CUSTOM_FIELDS@TTWOS wcf on w.WOR_OID = wcf.WCF_WOR_OID
join ITSD.CDM_SERVICES@TTWOS s on wcf.WCF_SRV_OID = s.SRV_OID
join ITSD.CDM_LOCATIONS@TTWOS l on wcf.WCF_LOC1_OID = l.LOC_OID
where 
   w.wor_cat_oid = 186230517228469679 --Плановые
   and w.wor_poo_oid = 230181784698473080 --GSM
    and (s.SRV_OID IN (
           285475754583703822, --Если услуга ППО - Обслуговування FTTB (вимір швидкості E2E FTTB Speed)
           256227522490960548) --ППО - Обслуговування FTTB (усунення недоліків ППР на ТКД/ВОЛЗ)
         )
         and w.wor_attachment_exists=1  and w.wor_actualfinish+365>sysdate
group by l.loc_sourceid
) tl on tl.locationid=l.locationid
	where l.LOCATIONID = :id",
            'file' => '../cfg/hp_templates/loc_tkd.html',
            'header' => '<link rel="stylesheet" type="text/css" href="../css/general.css">'
         ),
         'LOCATION_BEE' => array(
            'sql' => "select l.locationid, l.name, l.fullname, l.relativename, l.alias1,
           l.alias2, l.objectid, l.subtype, l.substatus, l.description,
           l.address, l.towncity, l.province, l.zip, l.responsible,
           l.telephone, l.fax, l.notes, l.createddate,
           l.lastmodifieddate, l.physicalx, l.physicaly, l.location2parentlocation,
           decode(l.markedfordelete, 1, 'Yes', 'No') as markedfordelete,
        lt.name location_type, pl.name parent_location,
        du.name create_user, dum.name modified_user, ps.name PROVISIONSTATUS,
        e1.value as category_, e2.value as group_, e3.value as company_,  e4.value as zone_,
        e5.value as vip_, e6.value as ac_, e7.value as site_type,
        sat.priority, sat.nmin, sat.nrp, sat.min, sat.pop, sat.pop,
        sat.agg, sat.msc_bsc, sat.rbs, sat.pop, sat.wmb, decode(sat.saturn, 1, 'Saturn', 'No') saturn
    from location_o l
    join locationtype lt on l.LOCATION2LOCATIONTYPE = lt.LOCATIONTYPEID
    left join LOCATION pl on l.LOCATION2PARENTLOCATION = pl.LOCATIONID
    left join DIMUSER du on l.CREATEDBY2DIMUSER = du.DIMUSERID
    left join DIMUSER dum on l.LASTMODIFIEDBY2DIMUSER = dum.DIMUSERID
    join PROVISIONSTATUS ps on l.location2PROVISIONSTATUS = ps.PROVISIONSTATUSID
    join sattab_locationsite_o sat on sat.locationid = l.locationid
    left join enumeration e1 on e1.TABLENAME = 'SATTAB_LOCATIONSITE'
                                              and e1.FIELDNAME = 'CATEGORY_'
                                              and e1.SEQUENCE = sat.category_
    left join enumeration e2 on e2.TABLENAME = 'SATTAB_LOCATIONSITE'
                                              and e2.FIELDNAME = 'GROUP_'
                                              and e2.SEQUENCE = sat.group_
    left join enumeration e3 on e3.TABLENAME = 'SATTAB_LOCATIONSITE'
                                              and e3.FIELDNAME = 'COMPANY_'
                                              and e3.SEQUENCE = sat.company_
    left join enumeration e4 on e4.TABLENAME = 'SATTAB_LOCATIONSITE'
                                              and e4.FIELDNAME = 'ZONE'
                                              and e4.SEQUENCE = sat.zone
    left join enumeration e5 on e5.TABLENAME = 'SATTAB_LOCATIONSITE'
                                              and e5.FIELDNAME = 'VIP'
                                              and e5.SEQUENCE = sat.vip
   left join enumeration e6 on e6.TABLENAME = 'SATTAB_LOCATIONSITE'
                                              and e6.FIELDNAME = 'AC_MORE_1000V'
                                              and e6.SEQUENCE = sat.ac_more_1000v
   left join enumeration e7 on e7.TABLENAME = 'SATTAB_LOCATIONSITE'
                                              and e7.FIELDNAME = 'SITE_TYPE'
                                              and e7.SEQUENCE = sat.site_type
	  where l.LOCATIONID = :id",
            'file' => '../cfg/hp_templates/loc_beesite.html',
            'header' => '<link rel="stylesheet" type="text/css" href="../css/general.css">'
         ),
         'LOCATION_PFJ' => array(
            'sql' => "select l.locationid, l.name, l.fullname, l.relativename, l.alias1,
           l.alias2, l.objectid, l.subtype, l.substatus, l.description,
           l.address, l.towncity, l.province, l.zip, l.responsible,
           l.telephone, l.fax, l.notes, l.createddate,
           l.lastmodifieddate, l.physicalx, l.physicaly, l.location2parentlocation,
           case nvl(l.markedfordelete,0) when 0 then 'No' else 'Yes' end as markedfordelete,
        lt.name location_type,
        pl.name parent_location,
        du.name create_user,
        dum.name modified_user,
        ps.name PROVISIONSTATUS,
        s.owner,
        s.contract,
        pfjt.value pfj_type,
         --replace(regexp_substr(l.physicalx,'\d{1,3}') + 
         --regexp_substr(l.physicalx,'\d{1,3}',3)/60 +
         --(regexp_substr(l.physicalx,'\d{1,3}',6)||'.'||regexp_substr(l.physicalx,'\d{1,3}',9))/3600,',','.') google_px,
         --replace(regexp_substr(l.physicaly,'\d{1,3}') + 
         --regexp_substr(l.physicaly,'\d{1,3}',3)/60 +
         --(regexp_substr(l.physicaly,'\d{1,3}',6)||'.'||regexp_substr(l.physicaly,'\d{1,3}',9))/3600,',','.') google_py
		 replace(ks_gis.SECtoG(l.physicalx),',','.') as google_px,
		 replace(ks_gis.SECtoG(l.physicaly),',','.') as google_py
    from location_o l
    join SATTAB_LOCATIONPFJ_o s on l.locationid = s.locationid
    left join enumeration pfjt on pfjt.TABLENAME = 'SATTAB_LOCATIONPFJ'
                          and pfjt.FIELDNAME = 'PFJ_TYPE'
                          and s.pfj_type = pfjt.SEQUENCE
    join locationtype lt on l.LOCATION2LOCATIONTYPE = lt.LOCATIONTYPEID
    left join LOCATION_o pl on l.LOCATION2PARENTLOCATION = pl.LOCATIONID
    left join DIMUSER du on l.CREATEDBY2DIMUSER = du.DIMUSERID
    left join DIMUSER dum on l.LASTMODIFIEDBY2DIMUSER = dum.DIMUSERID
    join PROVISIONSTATUS ps on l.location2PROVISIONSTATUS = ps.PROVISIONSTATUSID
    where l.LOCATIONID = :id",
            'file' => '../cfg/hp_templates/loc_pfj.html',
            'header' => '<link rel="stylesheet" type="text/css" href="../css/general.css">'
         ),
         'LOCATION_HOUSE' => array(
            'sql' => "select l.*,st.*,lp.name as PARENT_LOCATION, lp.alias2 as workgroup
 from location_o l
join sattab_locationhouse_o st on st.locationid=l.locationid
left join location_o lp on lp.locationid=l.location2parentlocation
    where l.LOCATIONID = :id",
            'file' => '../cfg/hp_templates/loc_house.html',
            'header' => '<link rel="stylesheet" type="text/css" href="../css/general.css">'
         )
      ),

   ),
   /////////////////////////////////////////////////////////////////////////////////////////////
   'node' => array(
      'psql' => "SELECT --n.*,
         n.nodeid, n.name, l.NAME location,
       nt.name node_type,
       nd.name node_def,
       n.fullname, n.relativename, n.alias1, n.alias2,
      n.objectid, n.subtype, n.substatus,
       n.description, n.notes,
       n.createddate,
       du.name create_user,
       n.lastmodifieddate,
       dum.name modified_user,
       ps.name PROVISIONSTATUS,
       fs.name functionalstatus,
       n.node2nodedef, n.node2nodetype,
       n.node2location,
       nt.TABLENAME sattab
   FROM NODE_o n
   join nodetype nt on n.NODE2NODETYPE = nt.NODETYPEID
   join nodedef nd on n.NODE2NODEDEF = nd.NODEDEFID
   left join DIMUSER du on n.CREATEDBY2DIMUSER = du.DIMUSERID
   left join DIMUSER dum on n.LASTMODIFIEDBY2DIMUSER = dum.DIMUSERID
   join PROVISIONSTATUS ps on n.NODE2PROVISIONSTATUS = ps.PROVISIONSTATUSID
   join LOCATION_o l on n.NODE2LOCATION = l.LOCATIONID
   left join status fs on n.node2functionalstatus = fs.statusid
   where n.nodeid = :id",
      'sattab' => array(
         'sql' => "select * from :table where nodeid = :id"
      ),
      'links' => array(
         'LOCATION' => array('id' => 'NODE2LOCATION', 'prefix' => 'locd')
      ),
      'switch_sql' => "select case when node2nodetype in (1900000060,
                                                            1900000073,
                                                            1900000074,
                                                            1900000075,
                                                            1900000076,
                                                            1900000085,
                                                            1900000086,
                                                            1900000087,
                                                            1900000091,
                                                            1900000089,
                                                            1900000141,
                                                            1900000151,
                                                            1900000162,
                                                            1900000175,
                                                            1900000183,
                                                            1900000182,
                                                            1900000109) and name like 'MDU%' /*and subtype is null*/ then 'FTTB_NODE'
                                       when node2nodetype in (1900000188) and (name like 'OLT%' or name like 'MDU%') then 'FTTB_NODE'
                                       when node2nodetype in (1900000063) then 'ODF_NODE'
                                       when node2nodetype in (1900000077, 1900000078, 1900000079) then 'ALCATEL_NODE' 
                                       when node2nodetype in (1900000056, 1900000054) then 'CISCO_NODE'     
                                       when node2nodetype in (1900000098) then 'SIU_NODE'
                                       when node2nodetype in (1860000033, 1900000154) then 'RBS_NODE'
                                       when node2nodetype in (1900000144) and node2nodedef not in (1900000279, 1900000360) then 'XDSL_NODE' 
									   when node2nodetype in (1900000124) then 'GW_NODE' end template
									   
                     from node_o
                     where nodeid = :id",
      'templates' => array(
         'FTTB_NODE' => array(
            'sql' => "SELECT  n.*,
        l.NAME location,
        l.DESCRIPTION address,
        l.TOWNCITY city,
        l.ALIAS2 GPO,
		l.alias1 status,
		l.relativename mdu,
        l.locationid,
	   regexp_substr(n.name, '[^/]*$') nodetype,
       nt.name node_type,
       nd.name node_def,
       du.name create_user,
       dum.name modified_user,
       ps.name PROVISIONSTATUS,
       fs.name functionalstatus,
       n.substatus as hop,
       st.*,
       case when mm.project_code like replace(substr(n.name,0,12),'_',' ') then mm.project_code else '<font color=red><b>'||mm.project_code||'</b></font>' end as mustang_location,
       p.name ds_port,
       regexp_replace(st.disk_err,'\(snmpget.*','') disc_err,
       substr(st.vlan,1,2)||'xx' vlans,
       case when st.ERPS_RING_ID is null then 'STP, priority='||st.STP_PRIORITY
            else 'ERPS, ring_id='||ERPS_RING_ID||decode(ERPS_OWNER_PORT,'1',' (Owner)','') end ringp,
       nud.nodeid nudid,
       decode(regexp_substr(n.name,'[^/]*$'),
       'Alcatel 6224', 'Alcatel6224.jpg',
       'Alcatel 6850', 'Alcatel6850.jpg',
       'D-Link DES 3200-26', 'DLink3200_26.jpg',
       'D-Link DES3200-26', 'DLink3028.jpg',
       'D-Link DES3028', 'DLink3028.jpg',
       'D-Link DES3200-28', 'DLink3200_28.jpg',
       'D-Link DGS-3627G', 'DLink3627.jpg',
       'Huawei Quidway S2326', 'HuaweiS2326.jpg',
       'OS6250-24M', 'OS6250.jpg',
       'ZyXEL MES3500-24', 'Zyxel_mes3500.jpg',
       'Huawei S2352', 'HuaweiS2352.jpg',
       'Huawei Quidway S5300', 'HuaweiS5300.jpg',
       'Huawei S2320', 'HuaweiS2320.png',
       'Ping3-knock', 'PING3knock.jpg',
       'Huawei S2350','1900000398.jpg',
       'Fengine S4800','1900000397.png',
       'Fengine S4820-28T-X','1900000397.png',
       'Fengine S4820-28T-TF','1900000404.png',
       'Fengine S4820-26T-X','1900000407.png',
       'Fengine S4830-28T-X','1900000420.png',
       'BDCOM GP3600','1900000419.png',
       'ISCOM2600-28X-RPS-AC','1900000424.jpg',
       'fttb_node.jpg') image,
	
CASE 
    WHEN dft.port1_status IS NOT NULL OR dft.port1_speed IS NOT NULL OR dft.port1_producer IS NOT NULL 
         OR dft.port1_serial IS NOT NULL OR dft.port1_type_model IS NOT NULL THEN
        'Status: ' || NVL(dft.port1_status, '-') ||
        ', Speed: ' || NVL(dft.port1_speed, '-') ||
        ', Producer: ' || NVL(dft.port1_producer, '-') ||
        ', Serial: ' || NVL(dft.port1_serial, '-') ||
        ', Type/Model: ' || NVL(dft.port1_type_model, '-')
END AS uplink,

CASE 
    WHEN dft.port2_status IS NOT NULL OR dft.port2_speed IS NOT NULL OR dft.port2_producer IS NOT NULL 
         OR dft.port2_serial IS NOT NULL OR dft.port2_type_model IS NOT NULL THEN
        'Status: ' || NVL(dft.port2_status, '-') ||
        ', Speed: ' || NVL(dft.port2_speed, '-') ||
        ', Producer: ' || NVL(dft.port2_producer, '-') ||
        ', Serial: ' || NVL(dft.port2_serial, '-') ||
        ', Type/Model: ' || NVL(dft.port2_type_model, '-')
END AS uplink2,

--       (select listagg( '<a href=homepage.php?el=port_'||p.portid||'>'||p.name||'</a> - <a href=homepage.php?el=serv_'||s.serviceid||'>'||decode(s.service2servicetype, 1900000009, s.name, st.name||' '||sb.name)||'</a>','<br>') WITHIN GROUP (order by s.name) b2b
       (select listagg( 'Port:'||p.name||' - '||decode(s.service2servicetype, 1900000009, s.name, st.name||' '||sb.name),';\n') WITHIN GROUP (order by s.name) b2b
    from port_vw p
    join porttype pt on p.port2porttype = pt.porttypeid
    join PROVISIONSTATUS ps on p.port2provisionstatus = ps.provisionstatusid
    left join port_vw cp on  p.portid = cp.parentport2port
    left join circuit_o cc on cp.portid = cc.circuit2startport or cp.portid = cc.circuit2endport
    join SERVICEOBJECT_o so on (so.SERVICEOBJECT2OBJECT = cc.circuitid)
                          and so.SERVICEOBJECT2DIMOBJECT = 3
    join SERVICE_o s on s.SERVICEID = so.SERVICEOBJECT2SERVICE and s.service2servicetype in (1900000023,1900000011, 1900000013, 1900000021, 1900000012,  1900000022,1900000020)
    join servicetype st on st.SERVICETYPEID = s.service2servicetype
    join subscriber_o sb on sb.subscriberid = s.service2subscriber
    where p.port2node = :id and p.parentport2port is null) b2b_services, 
       nd.POWERUSED
    
   FROM NODE_o n
   join nodetype nt on n.NODE2NODETYPE = nt.NODETYPEID
   join nodedef nd on n.NODE2NODEDEF = nd.NODEDEFID
   left join port_vw p on  p.portid = (n.alias2+0)
   left join DIMUSER du on n.CREATEDBY2DIMUSER = du.DIMUSERID
   left join DIMUSER dum on n.LASTMODIFIEDBY2DIMUSER = dum.DIMUSERID
   join PROVISIONSTATUS ps on n.NODE2PROVISIONSTATUS = ps.PROVISIONSTATUSID
   join LOCATION_o l on n.NODE2LOCATION = l.LOCATIONID
   left join status fs on n.node2functionalstatus = fs.statusid
   left join (select nodeid,ip_address,mac_address,hostname,serial_number,software,ERPS_RING_ID,STP_PRIORITY,ERPS_OWNER_PORT,vlan,disk_err from SATTAB_OS6250_24M_NODE_o
               union select nodeid,ip,mac,sysname,serial,software,ERPS_RING_ID,STP_PRIORITY,ERPS_OWNER_PORT,vlanid,'' disk_err from Sattab_Gt_Nodes2_o) st on n.nodeid = st.nodeid
   left join node_o nud on n.alias1 = nud.name
   left join KS_MUSTANG_SERIAL_NUMBERS mm on regexp_replace(mm.serial_number,'^\*|^S+|^KS|^SKS')=st.serial_number
   
   
LEFT JOIN (
    SELECT 
        nodeid,
        MAX(CASE WHEN port_num = 1 THEN status END) AS port1_status,
        MAX(CASE WHEN port_num = 1 THEN speed END) AS port1_speed,
        MAX(CASE WHEN port_num = 1 THEN producer END) AS port1_producer,
        MAX(CASE WHEN port_num = 1 THEN serial END) AS port1_serial,
        MAX(CASE WHEN port_num = 1 THEN type_model END) AS port1_type_model,

        MAX(CASE WHEN port_num = 2 THEN status END) AS port2_status,
        MAX(CASE WHEN port_num = 2 THEN speed END) AS port2_speed,
        MAX(CASE WHEN port_num = 2 THEN producer END) AS port2_producer,
        MAX(CASE WHEN port_num = 2 THEN serial END) AS port2_serial,
        MAX(CASE WHEN port_num = 2 THEN type_model END) AS port2_type_model
    FROM (
        SELECT 
            nodeid,
            port_index,
            status,
            speed,
            producer,
            serial,
            type_model,
            ROW_NUMBER() OVER (PARTITION BY nodeid ORDER BY TO_NUMBER(port_index)) AS port_num
        FROM (
            SELECT 
                nodeid,
                SUBSTR(oid, INSTR(oid, '.', -1) + 1) AS port_index,
                MAX(CASE WHEN oid LIKE '%1.3.6.1.2.1.2.2.1.8.%' THEN value END) AS status,
                MAX(CASE WHEN oid LIKE '%1.3.6.1.2.1.2.2.1.5.%' THEN value END) AS speed,
                MAX(CASE WHEN oid LIKE '%1.3.6.1.4.1.3807.3.305.2.1.29.%' THEN value END) AS producer,
                MAX(CASE WHEN oid LIKE '%1.3.6.1.4.1.3807.3.305.2.1.31.%' THEN value END) AS serial,
                MAX(CASE WHEN oid LIKE '%1.3.6.1.4.1.3807.3.305.2.1.43.%' THEN value END) AS type_model
            FROM KS.disco_fttb
            WHERE oid LIKE '%1.3.6.1.2.1.2.2.1.8.%' 
               OR oid LIKE '%1.3.6.1.2.1.2.2.1.5.%' 
               OR oid LIKE '%1.3.6.1.4.1.3807.3.305.2.1.29.%' 
               OR oid LIKE '%1.3.6.1.4.1.3807.3.305.2.1.31.%' 
               OR oid LIKE '%1.3.6.1.4.1.3807.3.305.2.1.43.%'
            GROUP BY nodeid, SUBSTR(oid, INSTR(oid, '.', -1) + 1)
        )
    )
    WHERE port_num <= 2
    GROUP BY nodeid
) dft ON dft.nodeid = n.nodeid


   where n.nodeid = :id",
            'file' => '../cfg/hp_templates/node_fttb.html',
            'header' => '<link rel="stylesheet" type="text/css" href="../css/fttb_node.css">'
         ),
         'XDSL_NODE' => array(
            'sql' => "SELECT  n.*,
        l.NAME location,
        l.DESCRIPTION address,
        l.TOWNCITY city,
        l.locationid,
	   regexp_substr(n.name, '[^/]*$') nodetype,
       nt.name node_type,
       nd.name node_def,
       du.name create_user,
       dum.name modified_user,
       ps.name PROVISIONSTATUS,
       fs.name functionalstatus,
       s.*,
       cs.login,
       c.circuitid,
       sp.name port,
       sn.name dslam,
       sn.nodeid snid,
       sp.portid spid,
       serv.serviceid,
       serv.name service,
       subs.name subscriber,
       subs.subscriberid
    FROM NODE_o n
   join nodetype nt on n.NODE2NODETYPE = nt.NODETYPEID
   join nodedef nd on n.NODE2NODEDEF = nd.NODEDEFID
   join SATTAB_XDLS_o s on n.nodeid = s.nodeid
   left join circuit_o c on c.circuit2endnode = n.nodeid and c.circuit2circuittype = 1900000044
   left join serviceobject_o so on c.circuitid = so.serviceobject2object
   left join service_o serv on so.serviceobject2service = serv.serviceid
   left join subscriber_o subs on serv.service2subscriber = subs.subscriberid   
   left join port_vw lsp on c.circuit2startport = lsp.portid
   left join port_vw sp on lsp.parentport2port = sp.portid
   left join node_o sn on sp.port2node = sn.nodeid
   left join SATTAB_PPPoE_Connection_o cs on c.circuitid = cs.circuitid
   left join DIMUSER du on n.CREATEDBY2DIMUSER = du.DIMUSERID
   left join DIMUSER dum on n.LASTMODIFIEDBY2DIMUSER = dum.DIMUSERID
   join PROVISIONSTATUS ps on n.NODE2PROVISIONSTATUS = ps.PROVISIONSTATUSID
   left join LOCATION_o l on sn.NODE2LOCATION = l.LOCATIONID
   left join status fs on n.node2functionalstatus = fs.statusid
   where n.nodeid = :id",
            'file' => '../cfg/hp_templates/node_xdsl.html',
            'header' => '<link rel="stylesheet" type="text/css" href="../css/fttb_node.css">'
         ),
         'ODF_NODE' => array(
            'sql' => "SELECT n.nodeid, n.name, l.NAME location, l.locationid, l.description address,
       nt.name node_type,
       nd.name node_def,
       n.fullname, n.relativename, n.alias1, n.alias2,
      n.objectid, n.subtype, n.substatus,
       n.description, n.notes,
       n.createddate,
       du.name create_user,
       n.lastmodifieddate,
       dum.name modified_user,
       ps.name PROVISIONSTATUS,
       fs.name functionalstatus,
       n.node2nodedef, n.node2nodetype,
       n.node2location,
       s.cabinet_number, 
       s.odf_number,
       s.name_linknc,
       enpt.value port_type
   FROM NODE_o n
   join SATTAB_ODF_NODE_o s on n.nodeid = s.nodeid
   left join enumeration enpt on enpt.fieldname = 'PORT_TYPE' and
                                 enpt.TABLENAME = 'SATTAB_ODF_NODE' and
                                 enpt.SEQUENCE = s.port_type
   join nodetype nt on n.NODE2NODETYPE = nt.NODETYPEID
   join nodedef nd on n.NODE2NODEDEF = nd.NODEDEFID
   left join DIMUSER du on n.CREATEDBY2DIMUSER = du.DIMUSERID
   left join DIMUSER dum on n.LASTMODIFIEDBY2DIMUSER = dum.DIMUSERID
   join PROVISIONSTATUS ps on n.NODE2PROVISIONSTATUS = ps.PROVISIONSTATUSID
   join LOCATION_o l on n.NODE2LOCATION = l.LOCATIONID
   left join status fs on n.node2functionalstatus = fs.statusid
   where n.nodeid = :id",
            'file' => '../cfg/hp_templates/node_odf.html',
            'header' => '<link rel="stylesheet" type="text/css" href="../css/general.css">'
         ),
         'GW_NODE' => array(
            'sql' => "SELECT n.nodeid, n.name, l.NAME location, l.locationid, l.description address,
       nt.name node_type,
       nd.name node_def,
       n.fullname, n.relativename, nvl(regexp_replace(ds.name,'\/.*$')||nvl2(pp.name,'/'||pp.name,pp.name),n.alias1) alias1, n.alias2,
       n.objectid, n.subtype, n.substatus,
       n.description, n.notes,
       n.createddate,
       nvl(ac.nav_user, du.name) create_user,
       n.lastmodifieddate,
       nvl(nvl(au.nav_user, ac.nav_user), dum.name) modified_user,
       ps.name PROVISIONSTATUS,
       fs.name functionalstatus,
       n.node2nodedef, n.node2nodetype,
       n.node2location,
       s.MAC, s.IP, s.MODEL,
       s.sysname,  s.syslocation, s.sysdescr,
	   s.serial
   FROM NODE_o n
   join SATTAB_GT_NODES2_o s on n.nodeid = s.nodeid
   join nodetype nt on n.NODE2NODETYPE = nt.NODETYPEID
   join nodedef nd on n.NODE2NODEDEF = nd.NODEDEFID
   left join DIMUSER du on n.CREATEDBY2DIMUSER = du.DIMUSERID
   left join DIMUSER dum on n.LASTMODIFIEDBY2DIMUSER = dum.DIMUSERID
   join PROVISIONSTATUS ps on n.NODE2PROVISIONSTATUS = ps.PROVISIONSTATUSID
   join LOCATION_o l on n.NODE2LOCATION = l.LOCATIONID
    left join circuit_o c on c.circuit2startnode = n.nodeid and c.subtype = 'VoiceGateway'
    left join port_vw p on c.circuit2endport = p.portid
    left join port_vw pp on p.parentport2port = pp.portid
    left join node_o ds on pp.port2node = ds.nodeid
   left join status fs on n.node2functionalstatus = fs.statusid
   left join ks_audit ac on ac.objid = n.nodeid and ac.operation = 'create' and ac.mtable = 'node'
   left join (select nav_user, objid from ks_audit where operation = 'update' and mtable = 'node' ) au on au.objid = n.nodeid
   where n.nodeid = :id",
            'file' => '../cfg/hp_templates/node_gw.html',
            'header' => '<link rel="stylesheet" type="text/css" href="../css/general.css">'
         ),
         'ALCATEL_NODE' => array(
            'sql' => "SELECT n.nodeid, n.name, l.NAME location, l.locationid, l.description address,
                       nt.name node_type,
                       nd.name node_def,
                       n.fullname, n.relativename, n.alias1, n.alias2,
                       n.objectid, n.subtype, n.substatus,
                       n.description, n.notes,
                       n.createddate, du.name create_user,
                       n.lastmodifieddate,
                       dum.name modified_user,
                       ps.name PROVISIONSTATUS,
                       fs.name functionalstatus,
                       n.node2nodedef, n.node2nodetype,
                       n.node2location,
                       st.*
                   FROM NODE_o n   
                   join nodetype nt on n.NODE2NODETYPE = nt.NODETYPEID
                   join nodedef nd on n.NODE2NODEDEF = nd.NODEDEFID
                   join (select * from sattab_7210_node_o
                   union select * from sattab_7750_node_o
                   union select * from sattab_7450_node_o ) st on st.nodeid = n.nodeid
                   left join DIMUSER du on n.CREATEDBY2DIMUSER = du.DIMUSERID
                   left join DIMUSER dum on n.LASTMODIFIEDBY2DIMUSER = dum.DIMUSERID
                   join PROVISIONSTATUS ps on n.NODE2PROVISIONSTATUS = ps.PROVISIONSTATUSID
                   join LOCATION_o l on n.NODE2LOCATION = l.LOCATIONID
                   left join status fs on n.node2functionalstatus = fs.statusid  
                   where n.nodeid =:id",
            'file' => '../cfg/hp_templates/node_alcatel.html',
            'header' => '<link rel="stylesheet" type="text/css" href="../css/general.css">'
         ),
         'CISCO_NODE' => array(
            'sql' => "SELECT n.nodeid, n.name, l.NAME location, l.locationid, l.description address,
                       nt.name node_type, nd.name node_def,
                       n.fullname, n.relativename, n.alias1, n.alias2,
                       n.objectid, n.subtype, n.substatus,
                       n.description, n.notes, n.createddate, du.name create_user,
                       n.lastmodifieddate, dum.name modified_user,
                       ps.name PROVISIONSTATUS, fs.name functionalstatus,
                       n.node2nodedef, n.node2nodetype,  n.node2location,  st.*
                   FROM NODE_o n   
                   join nodetype nt on n.NODE2NODETYPE = nt.NODETYPEID
                   join nodedef nd on n.NODE2NODEDEF = nd.NODEDEFID
                   join sattab_ciscoswitch_o st on st.nodeid = n.nodeid
                   left join DIMUSER du on n.CREATEDBY2DIMUSER = du.DIMUSERID
                   left join DIMUSER dum on n.LASTMODIFIEDBY2DIMUSER = dum.DIMUSERID
                   join PROVISIONSTATUS ps on n.NODE2PROVISIONSTATUS = ps.PROVISIONSTATUSID
                   join LOCATION_o l on n.NODE2LOCATION = l.LOCATIONID
                   left join status fs on n.node2functionalstatus = fs.statusid  
                   where n.nodeid =:id",
            'file' => '../cfg/hp_templates/node_cisco.html',
            'header' => '<link rel="stylesheet" type="text/css" href="../css/general.css">'
         ),
         'SIU_NODE' => array(
            'sql' => "SELECT n.nodeid, n.name, l.NAME location, l.locationid, l.description address,
                       nt.name node_type, nd.name node_def,
                       n.fullname, n.relativename, n.alias1, n.alias2,
                       n.objectid, n.subtype, n.substatus,
                       n.description, n.notes, n.createddate, du.name create_user,
                       n.lastmodifieddate, dum.name modified_user,
                       ps.name PROVISIONSTATUS, fs.name functionalstatus,
                       n.node2nodedef, n.node2nodetype,  n.node2location,  st.*
                   FROM NODE_o n   
                   join nodetype nt on n.NODE2NODETYPE = nt.NODETYPEID
                   join nodedef nd on n.NODE2NODEDEF = nd.NODEDEFID
                   join SATTAB_SIU_o st on st.nodeid = n.nodeid
                   left join DIMUSER du on n.CREATEDBY2DIMUSER = du.DIMUSERID
                   left join DIMUSER dum on n.LASTMODIFIEDBY2DIMUSER = dum.DIMUSERID
                   join PROVISIONSTATUS ps on n.NODE2PROVISIONSTATUS = ps.PROVISIONSTATUSID
                   join LOCATION_o l on n.NODE2LOCATION = l.LOCATIONID
                   left join status fs on n.node2functionalstatus = fs.statusid  
                   where n.nodeid =:id",
            'file' => '../cfg/hp_templates/node_siu.html',
            'header' => '<link rel="stylesheet" type="text/css" href="../css/general.css">'
         ),
         'RBS_NODE' => array(
            'sql' => "SELECT n.nodeid, n.name, l.NAME location, l.locationid, l.description address,
                       nt.name node_type, nd.name node_def,
                       n.fullname, n.relativename, n.alias1, n.alias2,
                       n.objectid, n.subtype, n.substatus,
                       n.description, n.notes, n.createddate, du.name create_user,
                       n.lastmodifieddate, dum.name modified_user,
                       ps.name PROVISIONSTATUS, fs.name functionalstatus,
                       n.node2nodedef, n.node2nodetype,  n.node2location,  st.*,
                        (case when n.name like 'U%' and n.node2nodetype = 1860000033 --3G E//
                                then  '<a href=http://BO4-1.kyivstar.ua:8080/BOE/OpenDocument/opendoc/openDocument.jsp?'||'&'||'sIDType=CUID'||'&'||
                                    'iDocID=Ab7BfiN_G1FHkhAYTsDrE5k'||'&'||'sRefresh=Y'||'&'||'lsSRBS=RBS_'||
                                     substr(n.name, 0, 6)||
                                    ' target=_blank>'||substr(n.name, 0, 6)||' WCDMA</a>'
                                 when n.name not like 'U%' and n.node2nodetype = 1860000033 --2G E//
                                      then  '<a href=http://BO4-1.kyivstar.ua:8080/BOE/OpenDocument/opendoc/openDocument.jsp?'||'&'||'sIDType=CUID'||'&'||
                                            'iDocID=ASTi0fhs38dHoKYY83SK2D0'||'&'||'sRefresh=Y'||'&'||'lsSRBS='||
                                            substr(n.name, 0,6)||'%25'||
                                                           ' target=_blank>'||substr(n.name, 0,6)||case when substr(n.name, 4, 1) < '5' then ' GSM' else ' DCS' end||'</a>' 
                                 when n.node2nodedef = 1900000320 then substr(n.name, 0,6)||' WCDMA Nokia' --3G Nokia
                                 when n.node2nodedef = 1900000318 then substr(n.name, 0,6)||(case when substr(n.name, 4, 1) < '5' then ' GSM Nokia' else ' DCS Nokia' end) --2G Nokia
                                 else substr(n.name, 0,6) end) traf
                   FROM NODE_o n   
                   join nodetype nt on n.NODE2NODETYPE = nt.NODETYPEID
                   join nodedef nd on n.NODE2NODEDEF = nd.NODEDEFID
                   join SATTAB_RBSCABINETNODE_o st on st.nodeid = n.nodeid
                   left join DIMUSER du on n.CREATEDBY2DIMUSER = du.DIMUSERID
                   left join DIMUSER dum on n.LASTMODIFIEDBY2DIMUSER = dum.DIMUSERID
                   join PROVISIONSTATUS ps on n.NODE2PROVISIONSTATUS = ps.PROVISIONSTATUSID
                   join LOCATION_o l on n.NODE2LOCATION = l.LOCATIONID
                   left join status fs on n.node2functionalstatus = fs.statusid  
                   where n.nodeid =:id",
            'file' => '../cfg/hp_templates/node_rbs.html',
            'header' => '<link rel="stylesheet" type="text/css" href="../css/general.css">'
         )
      )
   ),

   //////////////////////////////////////////////////////////////////////////////////////////////////////////////////
   'circ' => array(
      'psql' => "select c.*,
	     ct.name circuit_type,
	     b.NAME bandwidth,
	     sl.name start_location,
	     sn.name start_node,
	     sp.name start_port,
	     el.name end_location,
	     en.name end_node,
	     ep.name end_port,
	     du.NAME create_user,
	     dum.NAME modified_user,
	     ps.NAME PROVISIONSTATUS,
	     ct.tablename sattab
	 from circuit_o c
	 join circuitTYPE ct on ct.circuittypeID = c.circuit2circuitTYPE
	 left join BANDWIDTH b on b.BANDWIDTHID = c.circuit2BANDWIDTH
	 join location_o sl on c.circuit2STARTLOCATION = sl.LOCATIONID
	 join location_o el on c.circuit2ENDLOCATION = el.LOCATIONID
	 join node_o sn on c.CIRCUIT2STARTNODE = sn.NODEID
	 join node_o en on c.CIRCUIT2ENDNODE = en.NODEID
	 left join port sp on c.CIRCUIT2STARTPORT = sp.PORTID
	 left join port ep on c.CIRCUIT2ENDPORT = ep.PORTID
	 left join DIMUSER du on c.CREATEDBY2DIMUSER = du.DIMUSERID
	 left join DIMUSER dum on c.LASTMODIFIEDBY2DIMUSER = dum.DIMUSERID
	 join PROVISIONSTATUS ps on c.circuit2PROVISIONSTATUS = ps.PROVISIONSTATUSID
	 where c.circuitid = :id",
      'sattab' => array(
         'sql' => "select * from :table where circuitid = :id"
      ),
      'links' => array(
         'START_LOCATION' => array('id' => 'CIRCUIT2STARTLOCATION', 'prefix' => 'locd'),
         'END_LOCATION' => array('id' => 'CIRCUIT2ENDLOCATION', 'prefix' => 'locd'),
         'START_NODE' => array('id' => 'CIRCUIT2STARTNODE', 'prefix' => 'node'),
         'END_NODE' => array('id' => 'CIRCUIT2ENDNODE', 'prefix' => 'node'),
         'START_PORT' => array('id' => 'CIRCUIT2STARTPORT', 'prefix' => 'port'),
         'END_PORT' => array('id' => 'CIRCUIT2ENDPORT', 'prefix' => 'port')
      ),
      'switch_sql' => "select case when circuit2circuittype = 1800000001  then 'CIRC_MICR'
	   								when circuit2circuittype = 1900000031  then 'CIRC_MICR_DATA'
									when circuit2circuittype = 1900000042  then 'ALCATEL_DATA'
                                    when circuit2circuittype = 1900000044  then 'CIRC_PPPOE'
												end template 
									from circuit_o where  circuitid = :id ",
      'templates' => array(
         'CIRC_MICR' => array(
            'sql' => "--circ_micr
select c.circuitid, c.name, c.createddate, c.lastmodifieddate, du.NAME create_user,
							       dum.NAME modified_user,
							       ct.name circuit_type,
							       cd.NAME bandwidth,
							       sl.locationid start_locationid,
							       sl.name start_location,
							       sl.description start_addr,
							       sn.nodeid start_nodeid,
							       sn.name start_node,
							       el.locationid end_locationid,
							       el.name end_location,
							       el.description end_addr,
							       en.nodeid end_nodeid,
							       en.name end_node,
							       ps.NAME PROVISIONSTATUS,
							       decode(c.circuit2protectiontype, null, 'Working', 'Protection') protectionstate,
								   decode(prt.protectionid, null, '1+0', prt.protectiontype) protectiontype,
								   decode(c.circuit2protectiontype, null, prt.protectionid, prt.protectedid) protectioncircuitid,
                         		   decode(c.circuit2protectiontype, null, prt.protectionname, prt.protectedname) protectioncircuitname,
							       ec.cardid END_RAUID, ec.rautype END_RAU, EC.RAU_PRODUCTNUMBER END_RAUPRODUCTNUMBER,
								          EC.RX_FREQ END_RX_FREQ, EC.TX_FREQ END_TX_FREQ,
								          EC.MMUPRODUCTNUMBER END_MMUPRODUCTNUMBER, EC.MMUID END_MMUID, EC.MMU END_MMU,
								          Sc.cardid START_RAUID, Sc.rautype START_RAU, SC.RAU_PRODUCTNUMBER START_RAUPRODUCTNUMBER,
								          SC.RX_FREQ START_RX_FREQ, SC.TX_FREQ START_TX_FREQ,
       							   SC.MMUPRODUCTNUMBER START_MMUPRODUCTNUMBER, SC.MMUID START_MMUID, SC.MMU START_MMU
							   from circuit_o c
							   join circuitTYPE ct on ct.circuittypeID = c.circuit2circuitTYPE
							   join  circuitdef cd on c.circuit2circuitdef = cd.circuitdefid
							   join location_o sl on c.circuit2STARTLOCATION = sl.LOCATIONID
							   join location_o el on c.circuit2ENDLOCATION = el.LOCATIONID
							   join node_o sn on c.CIRCUIT2STARTNODE = sn.NODEID
							   join node_o en on c.CIRCUIT2ENDNODE = en.NODEID
							   left join port sp on c.CIRCUIT2STARTPORT = sp.PORTID
							   left join port ep on c.CIRCUIT2ENDPORT = ep.PORTID
							   left join (select c.cardid, nvl(rautn.type_of_swu, ct.NAME) rautype,
--							                     nvl(rauml.product_number, rautn.product_number) as rau_productnumber,
--							                     nvl(mmutn.base_rx_f_rf, rauml.rx_freq_ra) rx_fReq,
--							                     nvl(mmutn.base_tx_f_rf, rauml.tx_freq_ra) tx_freq,
--							                     nvl(mmutn.product_number, mmuml.product_number) mmuproductnumber,
                                 nvl(rauml.hardware_version, rautn.product_number) as rau_productnumber,
                                 nvl(mmutn.base_rx_f_rf, rauml.if1_rx) rx_freq,
                                 nvl(mmutn.base_tx_f_rf, rauml.if1_tx) tx_freq,
                                 nvl(mmutn.product_number, mmuml.hardware_version) mmuproductnumber,
							                     c1.name mmu, c1.cardid mmuid
							              from card_o c
							              join cardtype ct on c.card2cardtype = ct.cardtypeid
							              join SLOT_o sl on c.CARD2SHELFSLOT = sl.SLOTID
							              join SHELF_o sh on sh.SHELFID = sl.SLOT2SHELF
							              join CARDINSLOT_o cs on sl.SLOTID = cs.CARDINSLOT2SLOT
							              join card_o c1 on cs.CARDINSLOT2CARD = c1.CARDID
							              join cardtype ct1 on c1.card2cardtype = ct1.cardtypeid
							              left join sattab_tncard_o rautn on rautn.cardid = c.cardid
							              left join sattab_tncard_o mmutn on mmutn.cardid = c1.cardid
--							              left join sattab_minilinkcard_o rauml on rauml.cardid = c.cardid
--							              left join sattab_minilinkcard_o mmuml on mmuml.cardid = c1.cardid
left join SATTAB_U2000_CARD_O rauml on rauml.cardid = c.cardid
left join SATTAB_U2000_CARD_O mmuml on mmuml.cardid = c1.cardid
							                    ) sc on sc.cardid = sp.port2card
							   left join (select  c.cardid , nvl(rautn.type_of_swu, ct.NAME) rautype,
--							                   nvl(rauml.product_number, rautn.product_number) as rau_productnumber,
--							                   nvl(mmutn.base_rx_f_rf, rauml.rx_freq_ra) rx_freq,
--							                   nvl(mmutn.base_tx_f_rf, rauml.tx_freq_ra) tx_freq,
--							                   nvl(mmutn.product_number, mmuml.product_number) mmuproductnumber,
                                 nvl(rauml.hardware_version, rautn.product_number) as rau_productnumber,
                                 nvl(mmutn.base_rx_f_rf, rauml.if1_rx) rx_freq,
                                 nvl(mmutn.base_tx_f_rf, rauml.if1_tx) tx_freq,
                                 nvl(mmutn.product_number, mmuml.hardware_version) mmuproductnumber,
							                   c1.name mmu, c1.cardid mmuid
							              from card_o c
							              join cardtype ct on c.card2cardtype = ct.cardtypeid
							              join SLOT_o sl on c.CARD2SHELFSLOT = sl.SLOTID
							              join SHELF_o sh on sh.SHELFID = sl.SLOT2SHELF
							              join CARDINSLOT_o cs on sl.SLOTID = cs.CARDINSLOT2SLOT
							              join card_o c1 on cs.CARDINSLOT2CARD = c1.CARDID
							              join cardtype ct1 on c1.card2cardtype = ct1.cardtypeid
							              left join sattab_tncard_o rautn on rautn.cardid = c.cardid
							              left join sattab_tncard_o mmutn on mmutn.cardid = c1.cardid
--							              left join sattab_minilinkcard_o rauml on rauml.cardid = c.cardid
--							              left join sattab_minilinkcard_o mmuml on mmuml.cardid = c1.cardid
left join SATTAB_U2000_CARD_O rauml on rauml.cardid = c.cardid
left join SATTAB_U2000_CARD_O mmuml on mmuml.cardid = c1.cardid
							              ) ec on ec.cardid = ep.port2card
						left join (select distinct c1.circuitid protectionid, c1.name protectionname, pt.name protectiontype,
		                               c2.circuitid PROTECTEDID, c2.name PROTECTEDNAME
		                           from protection p
	                               join circuit_o c1 on c1.circuitid = p.prot2protectingobject
                                   JOIN PROTECTIONTYPE_M PT ON PT.PROTECTIONTYPEID = C1. CIRCUIT2PROTECTIONTYPE
								   join circuit c2 on c2.circuitid = p.prot2protectedobject
								   where p.prot2dimobject = 3 and
								         (c1.circuitid = :id or
								          c2.circuitid = :id)) prt on prt.PROTECTEDID = c.circuitid or
                                                                prt.protectionid = c.circuitid
							   left join DIMUSER du on c.CREATEDBY2DIMUSER = du.DIMUSERID
							   left join DIMUSER dum on c.LASTMODIFIEDBY2DIMUSER = dum.DIMUSERID
							   join PROVISIONSTATUS ps on c.circuit2PROVISIONSTATUS = ps.PROVISIONSTATUSID
   					where c.circuitid = :id
	               			",
            'file' => '../cfg/hp_templates/circ_micr.html',
            'header' => '<link rel="stylesheet" type="text/css" href="../css/general.css">'
         ),
         'CIRC_PPPOE' => array(
            'sql' => "select c.*,
                               ct.name circuit_type,      
                               sl.locationid start_locationid,
                               sl.name start_location,
                               sl.description start_address,
                               sn.name start_node,
                               sn.nodeid start_nodeid,
                               nvl(spp.portid, sp.portid) start_portid,
                               nvl(spp.name, sp.name) start_port,  
                               du.NAME create_user,
                               dum.NAME modified_user,
                               ps.NAME PROVISIONSTATUS,
                               ct.tablename sattab,
                               st.login, st.clientid, st.clientname, st.wonum, st.serviceid, st.radiusframedipaddress,
                               decode(st.block, 0, 'Unblock', 1, 'Block', 'Unknown') block, 
                               pp.name ppprofile,
                                pkgks_pppoe.getNet(st.radiusreplyitem, 1)||'/'|| pkgks_pppoe.getMask(st.radiusreplyitem, 1) ROUTE1,
                                decode(pkgks_pppoe.getNet(st.radiusreplyitem, 2), null, null, pkgks_pppoe.getNet(st.radiusreplyitem, 2)||'/'|| pkgks_pppoe.getMask(st.radiusreplyitem, 2)) ROUTE2,
                                decode(pkgks_pppoe.getNet(st.radiusreplyitem, 3), null, null,  pkgks_pppoe.getNet(st.radiusreplyitem, 3)||'/'|| pkgks_pppoe.getMask(st.radiusreplyitem, 3)) ROUTE3, 
                                pkgks_pppoe.getAclIn(st.radiusreplyitem) AclIN,
                                pkgks_pppoe.getAclOut(st.radiusreplyitem) AclOut,
                                replace(st.radiusreplyitem,chr(10),'<br>') radiusreplyitem,
                               s.serviceid SERVICEID, s.name SERVICENAME, s.substatus SERVICESTATE, sst.point_a,  sst.dmsserviceid,
                               sb.description SUBSCRIBER, sb.name SUBSCRIBERNAME, sb.objectid LS, sb.relativename OKPO, sb.alias2 CLASS, sb.alias1 SEGM, sb.notes as sm,
                               sbst.dms_id, sbst.siebel_id, dn.dimnumberid
                           from circuit_o c
                           join circuitTYPE ct on ct.circuittypeID = c.circuit2circuitTYPE
                           join location_o sl on c.circuit2STARTLOCATION = sl.LOCATIONID
                           join node_o sn on c.CIRCUIT2STARTNODE = sn.NODEID   
                           join port sp on c.CIRCUIT2STARTPORT = sp.PORTID  
                           left join port spp on sp.parentport2port = spp.portid
                           left join DIMUSER du on c.CREATEDBY2DIMUSER = du.DIMUSERID
                           left join DIMUSER dum on c.LASTMODIFIEDBY2DIMUSER = dum.DIMUSERID
                           join PROVISIONSTATUS ps on c.circuit2PROVISIONSTATUS = ps.PROVISIONSTATUSID
                           join SATTAB_PPPoE_Connection_o st on st.circuitid = c.circuitid
                           left join SERVICEOBJECT_o so on so.SERVICEOBJECT2DIMOBJECT = 3 and so.SERVICEOBJECT2OBJECT = c.circuitid
                           left join service_o s on so.SERVICEOBJECT2SERVICE = s.serviceid
                           left join sattab_sevice_b2b_o sst on sst.serviceid = s.serviceid
                           left join subscriber_o sb on sb.subscriberid = s.service2subscriber
                           left join sattab_subscriber_o sbst on sbst.subscriberid = sb.subscriberid
                           left join ks.pppoe_profile pp on pp.id = pkgks_pppoe.getProfile(st.radiusreplyitem)
                           left join dimnumber dn on dn.name = st.radiusframedipaddress||'/32' and dn.DIMNUMBER2DIMNUMBERTYPE =  1900000045
                           where c.circuitid =:id
	               			",
            'file' => '../cfg/hp_templates/circ_pppoe.html',
            'header' => '<link rel="stylesheet" type="text/css" href="../css/general.css">'
         ),
         'ALCATEL_DATA' => array(
            'sql' => "--alcatel_data
select c.circuitid, c.name, c.objectid,
							   ct.name circuit_type,
							   						
							   st.*,
							   sl.name start_location,
							   sl.locationid start_locationid,
							   sl.description start_addr,							  
							   sn.name start_node,
							   sn.nodeid start_nodeid,
							    en.nodeid end_nodeid,
							   decode(en.name, sn.name, null, el.name) end_location,
							   decode(en.name, sn.name, null, el.locationid) end_locationid,
							   decode(en.name, sn.name, null, el.description) end_addr,
							   decode(en.name, sn.name, null, en.name) end_node,   
							   du.NAME create_user,
							   dum.NAME modified_user,
							   c.subtype, 
							   c.createddate,
							   c.lastmodifieddate,
							   sp.parentport2port start_portid,
								ep.parentport2port end_portid
						   from circuit_o c
						   join circuitTYPE ct on ct.circuittypeID = c.circuit2circuitTYPE   
						   join SATTAB_ALCATEL_DATA_CIRCUIT_o st on st.circuitid = c.circuitid
						   join location_o sl on c.circuit2STARTLOCATION = sl.LOCATIONID
						   join location_o el on c.circuit2ENDLOCATION = el.LOCATIONID
						   join node_o sn on c.CIRCUIT2STARTNODE = sn.NODEID
						   join node_o en on c.CIRCUIT2ENDNODE = en.NODEID   
						   left join DIMUSER du on c.CREATEDBY2DIMUSER = du.DIMUSERID
						   left join DIMUSER dum on c.LASTMODIFIEDBY2DIMUSER = dum.DIMUSERID
						   left join port sp on sp.portid = c.circuit2startport
						   left join port ep on ep.portid = c.circuit2endport
						   where c.circuitid = :id
	               			",
            'file' => '../cfg/hp_templates/circ_alcatel.html',
            'header' => '<link rel="stylesheet" type="text/css" href="../css/general.css">'
         ),
         'CIRC_MICR_DATA' => array(
            'sql' => "--cir_micr_data
select c.circuitid, c.name, c.createddate, c.lastmodifieddate,
											   du.NAME create_user,  dum.NAME modified_user,
											   sat.adaptive_modulation, sat.capacity, sat.e1_number, sat.modulation,
											   sat.packet_link_capacity, sat.traffic_type, sat.xpic,
											   ct.name circuit_type, b.NAME bandwidth, ps.NAME PROVISIONSTATUS,
											   decode(c.circuit2protectiontype, null, 'Working', 'Protection') protectionstate,
						                       decode(prt.protectionid, null, '1+0', prt.protectiontype) protectiontype,
						                       decode(c.circuit2protectiontype, null, prt.protectionid, prt.protectedid) protectioncircuitid,
                         					   decode(c.circuit2protectiontype, null, prt.protectionname, prt.protectedname) protectioncircuitname,
											   sl.locationid start_locationid,  sl.name start_location, sl.description start_addr,
											   sn.nodeid start_nodeid, sn.name start_node,
											   el.locationid end_locationid, el.name end_location, el.description end_addr,
											   en.nodeid end_nodeid, en.name end_node,
											   ec.cardid END_RAUID, ec.rautype END_RAU, EC.RAU_PRODUCTNUMBER END_RAUPRODUCTNUMBER,
											   EC.RX_FREQ END_RX_FREQ, EC.TX_FREQ END_TX_FREQ,
											   EC.MMUPRODUCTNUMBER END_MMUPRODUCTNUMBER, EC.MMUID END_MMUID, EC.MMU END_MMU,
											   Sc.cardid START_RAUID, Sc.rautype START_RAU, SC.RAU_PRODUCTNUMBER START_RAUPRODUCTNUMBER,
											   SC.RX_FREQ START_RX_FREQ, SC.TX_FREQ START_TX_FREQ,
											   SC.MMUPRODUCTNUMBER START_MMUPRODUCTNUMBER, SC.MMUID START_MMUID, SC.MMU START_MMU
										from circuit_o c
										join circuitTYPE ct on ct.circuittypeID = c.circuit2circuitTYPE
										join BANDWIDTH b on b.BANDWIDTHID = c.circuit2BANDWIDTH
										join sattab_mbd_circuit_o sat on sat.circuitid = c.circuitid
										join location_o sl on c.circuit2STARTLOCATION = sl.LOCATIONID
										join location_o el on c.circuit2ENDLOCATION = el.LOCATIONID
										join node_o sn on c.CIRCUIT2STARTNODE = sn.NODEID
										join node_o en on c.CIRCUIT2ENDNODE = en.NODEID
										join port spp on c.CIRCUIT2STARTPORT = spp.PORTID
										join port sp on spp.parentport2port = sp.portid
										join port epp on c.CIRCUIT2ENDPORT = epp.PORTID
										join port ep on epp.parentport2port =ep.portid
										join (select c.cardid, nvl(rautn.type_of_swu, ct.NAME) rautype,
--											   nvl(rauml.product_number, rautn.product_number) as rau_productnumber,
--											   nvl(mmutn.base_rx_f_rf, rauml.rx_freq_ra) rx_fReq,
--											   nvl(mmutn.base_tx_f_rf, rauml.tx_freq_ra) tx_freq,
--											   nvl(mmutn.product_number, mmuml.product_number) mmuproductnumber,
                                 nvl(rauml.hardware_version, rautn.product_number) as rau_productnumber,
                                 nvl(mmutn.base_rx_f_rf, rauml.if1_rx) rx_freq,
                                 nvl(mmutn.base_tx_f_rf, rauml.if1_tx) tx_freq,
                                 nvl(mmutn.product_number, mmuml.hardware_version) mmuproductnumber,
											   c1.name mmu, c1.cardid mmuid
											from card_o c
											join cardtype ct on c.card2cardtype = ct.cardtypeid
											join SLOT_o sl on c.CARD2SHELFSLOT = sl.SLOTID
											join SHELF_o sh on sh.SHELFID = sl.SLOT2SHELF
											join CARDINSLOT_o cs on sl.SLOTID = cs.CARDINSLOT2SLOT
											join card_o c1 on cs.CARDINSLOT2CARD = c1.CARDID
											join cardtype ct1 on c1.card2cardtype = ct1.cardtypeid
											left join sattab_tncard_o rautn on rautn.cardid = c.cardid
											left join sattab_tncard_o mmutn on mmutn.cardid = c1.cardid
--											left join sattab_minilinkcard_o rauml on rauml.cardid = c.cardid
--											left join sattab_minilinkcard_o mmuml on mmuml.cardid = c1.cardid
left join SATTAB_U2000_CARD_O rauml on rauml.cardid = c.cardid
left join SATTAB_U2000_CARD_O mmuml on mmuml.cardid = c1.cardid
										  ) sc on sc.cardid = sp.port2card
										join (select  c.cardid , nvl(rautn.type_of_swu, ct.NAME) rautype,
--												 nvl(rauml.product_number, rautn.product_number) as rau_productnumber,
--												 nvl(mmutn.base_rx_f_rf, rauml.rx_freq_ra) rx_freq,
--												 nvl(mmutn.base_tx_f_rf, rauml.tx_freq_ra) tx_freq,
--												 nvl(mmutn.product_number, mmuml.product_number) mmuproductnumber,
                                 nvl(rauml.hardware_version, rautn.product_number) as rau_productnumber,
                                 nvl(mmutn.base_rx_f_rf, rauml.if1_rx) rx_freq,
                                 nvl(mmutn.base_tx_f_rf, rauml.if1_tx) tx_freq,
                                 nvl(mmutn.product_number, mmuml.hardware_version) mmuproductnumber,
												 c1.name mmu, c1.cardid mmuid
											from card_o c
											join cardtype ct on c.card2cardtype = ct.cardtypeid
											join SLOT_o sl on c.CARD2SHELFSLOT = sl.SLOTID
											join SHELF_o sh on sh.SHELFID = sl.SLOT2SHELF
											join CARDINSLOT_o cs on sl.SLOTID = cs.CARDINSLOT2SLOT
											join card_o c1 on cs.CARDINSLOT2CARD = c1.CARDID
											join cardtype ct1 on c1.card2cardtype = ct1.cardtypeid
											left join sattab_tncard_o rautn on rautn.cardid = c.cardid
											left join sattab_tncard_o mmutn on mmutn.cardid = c1.cardid
--											left join sattab_minilinkcard_o rauml on rauml.cardid = c.cardid
--											left join sattab_minilinkcard_o mmuml on mmuml.cardid = c1.cardid
left join SATTAB_U2000_CARD_O rauml on rauml.cardid = c.cardid
left join SATTAB_U2000_CARD_O mmuml on mmuml.cardid = c1.cardid
										) ec on ec.cardid = ep.port2card
										left join (select distinct c1.circuitid protectionid, c1.name protectionname, pt.name protectiontype,
										                               c2.circuitid PROTECTEDID, c2.name PROTECTEDNAME
										                               from protection p
										                               join circuit_o c1 on c1.circuitid = p.prot2protectingobject
										                               JOIN PROTECTIONTYPE_M PT ON PT.PROTECTIONTYPEID = C1. CIRCUIT2PROTECTIONTYPE
										                               join circuit_o c2 on c2.circuitid = p.prot2protectedobject
										                               where p.prot2dimobject = 3 and
										                                    (c1.circuitid = :id or
										                                    c2.circuitid = :id)) prt on prt.PROTECTEDID = c.circuitid or
                                                                prt.protectionid = c.circuitid
										left join DIMUSER du on c.CREATEDBY2DIMUSER = du.DIMUSERID
										left join DIMUSER dum on c.LASTMODIFIEDBY2DIMUSER = dum.DIMUSERID
										join PROVISIONSTATUS ps on c.circuit2PROVISIONSTATUS = ps.PROVISIONSTATUSID
										where c.circuitid = :id
				               			",
            'file' => '../cfg/hp_templates/circ_micr_data.html',
            'header' => '<link rel="stylesheet" type="text/css" href="../css/general.css">'
         )
      )
   ),
   //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
   'serv' => array(
      'psql' => "select s.service2servicetype, s.serviceid, s.name, s.fullname, s.relativename, s.alias1,
			   s.alias2, s.objectid, s.subtype, s.substatus, s.description, s.notes,
			   s.createddate, s.lastmodifieddate, s.service2subscriber,
				st.NAME service_type,
			su.name subscriber,
			du.name create_user,
			dum.name modified_user,
			ps.name PROVISIONSTATUS,
			st.tablename sattab
		from service_o s
		join serviceTYPE st on s.service2serviceTYPE = st.serviceTYPEID
		join SUBSCRIBER_o su on su.SUBSCRIBERID = s.SERVICE2SUBSCRIBER
		left join DIMUSER du on s.CREATEDBY2DIMUSER = du.DIMUSERID
		left join DIMUSER dum on s.LASTMODIFIEDBY2DIMUSER = dum.DIMUSERID
		join PROVISIONSTATUS ps on s.service2PROVISIONSTATUS = ps.PROVISIONSTATUSID
		where s.serviceID = :id",
      'sattab' => array(
         'sql' => "select * from :table where serviceid = :id"
      ),
      'links' => array(
         'SUBSCRIBER' => array('id' => 'SERVICE2SUBSCRIBER', 'prefix' => 'subs')
      ),
      'switch_sql' => "select case when service2servicetype in (1900000023, 1900000011, 1900000013, 1900000021, 1900000012) then 'B2B_SERV'
         						      when  service2servicetype in (1900000022, 1900000020 ) then 'B2C_SERV'  end template
                     from service_o
                     where serviceid = :id ",
      'templates' => array(
         'B2B_SERV' => array(
            'sql' => "
               			select s.service2servicetype, s.serviceid, s.name, s.alias1 ident1, s.alias2,
						      s.fullname ident2, s.objectid, s.subtype, s.substatus, s.description, s.notes,
						      s.createddate, s.lastmodifieddate, s.service2subscriber, s.notes,
						      st.NAME service_type, su.name subscriber, du.name create_user,dum.name modified_user,
						      ps.name PROVISIONSTATUS, sat.point_a, sat.interface_a,sat.point_b,sat.point_b,
						      sat.interface_b,sat.con_type, replace(sat.msisdn, ',', ',<br>') MSISDN, sat.line_count, sat.speed,
						      sat.class, sat.tech,sat.channel_type, sat.ip,t.ip_plan,sat.channnel_lenght,sat.activation_date,
						      sat.add_contact_number,sat.sla_percent, sat.sla_hours, sat.email,
						      sat.phone, sat.sla, sat.contactperson, sat.ip_conf, sat.pop, sat.DMSSERVICEID, dms.dms_info,
						      su.subscriberid, su.name subscriber, su.description as subsname, su.relativename as OKPO,  su.alias1 as segm,
       						  su.alias2 clas, su.objectid ls, su.subtype company, su.notes as sm, sat.rtpl_name, sbsat.dms_id, sbsat.siebel_id,
							  ttr.REMEDYDL

							from service_o s
						    join serviceTYPE st on s.service2serviceTYPE = st.serviceTYPEID
						    join sattab_sevice_b2b_o sat on sat.serviceid = s.serviceid
						    join SUBSCRIBER_o su on su.SUBSCRIBERID = s.SERVICE2SUBSCRIBER
						    join sattab_subscriber_o sbsat on sbsat.subscriberid = su.subscriberid
							left outer join KS_TTWOS_REMEDY ttr on decode (su.alias2,
								'Platinum', 0, 
								'Платиновий', 0, 
								'Платиновый', 0, 
								'Gold', 1, 
								'Золотий', 1, 
								'Золотой', 1, 
								'Silver', 2,
								'Срібний', 2,
								'Серебряный', 2,
								'Standard', 3,
								'Стандартний', 3,
								'Стандартный', 3,  
							4) = ttr.SERVICECLASS
                left join ks_dms_ipline dms on dms.service_dms=sat.dmsserviceid 
                left join (
                    select numo.numberobject2object as serviceid,listagg(n.name,';') WITHIN GROUP (order by n.name) as ip_plan from dimnumber_o n
                    join numberobject_o numo on numo.numberobject2number=n.dimnumberid
                    where n.dimnumber2dimnumbertype=1900000045 and n.dimnumber2provisionstatus=1900000028
                    group by numo.numberobject2object
                ) t on t.serviceid=s.serviceid
						    left join DIMUSER du on s.CREATEDBY2DIMUSER = du.DIMUSERID
						    left join DIMUSER dum on s.LASTMODIFIEDBY2DIMUSER = dum.DIMUSERID
							join PROVISIONSTATUS ps on s.service2PROVISIONSTATUS = ps.PROVISIONSTATUSID
		where s.serviceID = :id
               			",
            'file' => '../cfg/hp_templates/serv_b2b.html',
            'header' => '<link rel="stylesheet" type="text/css" href="../css/general.css">'
         ),
         'B2C_SERV' => array(
            'sql' => "
		               			select s.service2servicetype, s.serviceid, s.name, s.alias1 ident1,
								      s.alias2 ident2, s.objectid, s.subtype, s.substatus, s.description, s.notes,
								      s.createddate, s.lastmodifieddate, s.service2subscriber,
								      st.NAME service_type, su.name subscriber, du.name create_user,dum.name modified_user,
								      ps.name PROVISIONSTATUS, sat.point_a, sat.interface_a,sat.point_b,sat.point_b,
								      sat.interface_b,sat.con_type, sat.msisdn, sat.line_count, sat.speed,
								      sat.class, sat.tech,sat.channel_type, sat.ip,sat.channnel_lenght,sat.activation_date,
								      sat.add_contact_number,sat.sla_percent, sat.sla_hours, sat.email,
								      sat.phone, sat.sla, sat.contactperson, sat.ip_conf,
								      su.subscriberid, su.name subscriber, su.description as subsname, su.relativename as OKPO,  su.alias1 as segm,
		       						  su.alias2 clas, su.objectid ls, su.subtype company, su.notes as sm
								    from service_o s
								    join serviceTYPE st on s.service2serviceTYPE = st.serviceTYPEID
								    join sattab_sevice_b2b_o sat on sat.serviceid = s.serviceid
								    join SUBSCRIBER_o su on su.SUBSCRIBERID = s.SERVICE2SUBSCRIBER
								    left join DIMUSER du on s.CREATEDBY2DIMUSER = du.DIMUSERID
								    left join DIMUSER dum on s.LASTMODIFIEDBY2DIMUSER = dum.DIMUSERID
									join PROVISIONSTATUS ps on s.service2PROVISIONSTATUS = ps.PROVISIONSTATUSID
				where s.serviceID = :id
		               			",
            'file' => '../cfg/hp_templates/serv_b2c.html',
            'header' => '<link rel="stylesheet" type="text/css" href="../css/general.css">'
         )
      )
   ),
   ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
   'subs' => array(
      'psql' => "select s.*,
					   st.NAME subscriber_type,
					   du.name create_user,
					   dum.name modified_user,
					   ps.name PROVISIONSTATUS,
                        st.tablename sattab
				   from subscriber_o s
				   join SUBSCRIBERTYPE st on s.SUBSCRIBER2SUBSCRIBERTYPE = st.SUBSCRIBERTYPEID
				   left join DIMUSER du on s.CREATEDBY2DIMUSER = du.DIMUSERID
				   left join DIMUSER dum on s.LASTMODIFIEDBY2DIMUSER = dum.DIMUSERID
				   join PROVISIONSTATUS ps on s.SUBSCRIBER2PROVISIONSTATUS = ps.PROVISIONSTATUSID
					where s.SUBSCRIBERID =  :id",
      'switch_sql' => "select case when subscriber2subscribertype in (1900000006) then 'B2B_SUBS'
            							 when subscriber2subscribertype in (1900000013)then 'B2C_SUBS' end template
                        from subscriber_o
                        where subscriberid = :id ",
      'templates' => array(
         'B2B_SUBS' => array(
            'sql' => "
                  			select s.*,
									sat.*,
							st.NAME subscriber_type,
							du.name create_user,
							dum.name modified_user,
							ps.name PROVISIONSTATUS
											   from subscriber_o s
											   join sattab_subscriber_o sat on sat.subscriberid = s.subscriberid
											   join SUBSCRIBERTYPE st on s.SUBSCRIBER2SUBSCRIBERTYPE = st.SUBSCRIBERTYPEID
											   left join DIMUSER du on s.CREATEDBY2DIMUSER = du.DIMUSERID
											   left join DIMUSER dum on s.LASTMODIFIEDBY2DIMUSER = dum.DIMUSERID
											   join PROVISIONSTATUS ps on s.SUBSCRIBER2PROVISIONSTATUS = ps.PROVISIONSTATUSID
					where s.SUBSCRIBERID =  :id
                  			",
            'file' => '../cfg/hp_templates/subs_b2b.html',
            'header' => '<link rel="stylesheet" type="text/css" href="../css/general.css">'
         ),
         'B2C_SUBS' => array(
            'sql' => "
			                    			select s.*,
			  							st.NAME subscriber_type,
			  							du.name create_user,
			  							dum.name modified_user,
			  							ps.name PROVISIONSTATUS
			  											   from subscriber_o s
			  											   join SUBSCRIBERTYPE st on s.SUBSCRIBER2SUBSCRIBERTYPE = st.SUBSCRIBERTYPEID
			  											   left join DIMUSER du on s.CREATEDBY2DIMUSER = du.DIMUSERID
			  											   left join DIMUSER dum on s.LASTMODIFIEDBY2DIMUSER = dum.DIMUSERID
			  											   join PROVISIONSTATUS ps on s.SUBSCRIBER2PROVISIONSTATUS = ps.PROVISIONSTATUSID
			  					where s.SUBSCRIBERID =  :id
			                    			",
            'file' => '../cfg/hp_templates/subs_b2c.html',
            'header' => '<link rel="stylesheet" type="text/css" href="../css/general.css">'
         )
      )
   ),
   ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
   'cable' => array(
      'psql' => "select c.*,
					   ct.NAME cable_type,
					   du.name create_user,
					   dum.name modified_user,
					   ps.name PROVISIONSTATUS,
                       ct.tablename sattab
				   from cable c
				   join cabletype ct on ct.CABLETYPEID = c.cable2cabletype  
					left join DIMUSER du on c.CREATEDBY2DIMUSER = du.DIMUSERID
					left join DIMUSER dum on c.LASTMODIFIEDBY2DIMUSER = dum.DIMUSERID
					join PROVISIONSTATUS ps on c.cable2PROVISIONSTATUS = ps.PROVISIONSTATUSid
					where c.cableid =  :id",
      'sattab' => array(
         'sql' => "select * from :table where cableid = :id"
      ),
      'switch_sql' => "select decode(substr(ct.name, 0,1), 'F', 'Fiber', 'C', 'Copper', 'Unk') template
                        from cable c 
						join cabletype ct on ct.CABLETYPEID = c.cable2cabletype 
                        where cableid = :id ",
      'templates' => array(
         'Fiber' => array(
            'sql' => "select c.cableid, 
                c.name,     
                decode(substr(ct.name, 0,1), 'C', 'Copper ', 'F', 'Fiber ', 'Unkn')||ct.name cable_type,   
                c.alias1, c.alias2,c.fullname,c.relativename, c.objectid, c.subtype,  c.substatus, c.description, substr(c.description,0,50)||'...' as desclink, c.notes, c.markedfordelete,
                decode(ps.provisionstatusid,1900000036,'<font color=red>'||UPPER(ps.NAME)||'</font>',ps.NAME) PROVISIONSTATUS,  e1.value FEATURE1, e2.value FEATURE2,  e3.value FEATURE3, e4.value CONDUCTOR_TYPE,
                e5.value OWNER, e6.value RESPONSIBILITY, e7.value SERVICE, s.manufacturer, s.asup_name, s.pm_name,  s.contract,  s.cableid_owners,
                s.outside_diam, s.blocked_cond,  c.createddate,  du.NAME create_user, c.lastmodifieddate,  dum.NAME modified_user,
                mcl.total_length, total_sections, 
                sl.name start_loc, sl.locationid start_locid,
                el.name end_loc, el.locationid end_locid,
                e8.value as FORM_OF_OWNERSHIP
              from cable c
              left join (select min(cl.sequence) min_cl, max(cl.sequence) max_cl, sum(cl.SECTIONLENGTH) total_length, count(*) total_sections, CBLC2CABLE cableid
                        FROM CABLELOCATION cl 
                        group by CBLC2CABLE) mcl on c.cableid = mcl.cableid
              left join CABLELOCATION scl on scl.CBLC2CABLE = c.cableid and scl.sequence=mcl.min_cl
              left join location_o sl on scl.CBLC2LOCATION = sl.LOCATIONID
              left join CABLELOCATION ecl on ecl.CBLC2CABLE = c.cableid and ecl.sequence=mcl.max_cl
              left join location_o el on ecl.CBLC2LOCATION = el.LOCATIONID
              join SATTAB_cable s on s.cableid = c.cableid
              join cabletype ct on ct.CABLETYPEID = c.cable2cabletype  
              left join DIMUSER du on c.CREATEDBY2DIMUSER = du.DIMUSERID
              left join DIMUSER dum on c.LASTMODIFIEDBY2DIMUSER = dum.DIMUSERID
              join PROVISIONSTATUS ps on c.cable2PROVISIONSTATUS = ps.PROVISIONSTATUSID
              left join enumeration e1 on e1.fieldname = 'FEATURE1' 
                      and e1.tablename ='SATTAB_CABLE'  
                      and s.feature1 = e1.sequence
                left join enumeration e2 on e2.fieldname = 'FEATURE2'
                      and e2.tablename ='SATTAB_CABLE'  
                      and s.feature2 = e2.sequence
                left join enumeration e3 on e3.fieldname = 'FEATURE3'
                      and e3.tablename ='SATTAB_CABLE'  
                      and s.feature3 = e3.sequence
                left join enumeration e4 on e4.fieldname = 'CONDUCTOR_TYPE'
                      and e4.tablename ='SATTAB_CABLE'  
                      and s.conductor_type = e4.sequence
                left join enumeration e5 on e5.fieldname = 'OWNER'
                      and e5.tablename ='SATTAB_CABLE'  
                      and s.owner = e5.sequence
                left join enumeration e6 on e6.fieldname = 'RESPONSIBILITY'
                      and e6.tablename ='SATTAB_CABLE'  
                      and s.responsibility = e6.sequence
                left join enumeration e7 on e7.fieldname = 'SERVICE'
                      and e7.tablename ='SATTAB_CABLE'  
                      and s.service = e7.sequence
                left join enumeration e8 on e8.fieldname = 'FORM_OF_OWNERSHIP'
                      and e8.tablename ='SATTAB_CABLE'  
                      and s.form_of_ownership = e8.sequence
              where c.cableid = :id",
            'file' => '../cfg/hp_templates/cable_fiber.html',
            'header' => '<link rel="stylesheet" type="text/css" href="../css/general.css">'
         ),
         'Copper' => array(
            'sql' => "select c.cableid, 
                c.name,     
                decode(substr(ct.name, 0,1), 'C', 'Copper ', 'F', 'Fiber ', 'Unkn')||ct.name cable_type,   
                c.alias1, c.alias2,c.fullname,c.relativename, c.objectid, c.subtype,  c.substatus, c.description, c.notes, c.markedfordelete,
                decode(ps.provisionstatusid,1900000036,'<font color=red>'||UPPER(ps.NAME)||'</font>',ps.NAME) PROVISIONSTATUS,  e1.value FEATURE1, e2.value FEATURE2,  e3.value FEATURE3, e4.value CONDUCTOR_TYPE,
                e5.value OWNER, 
                --e6.value RESPONSIBILITY, 
                e7.value SERVICE, s.manufacturer, s.asup_name, s.pm_name,  s.contract,  s.cableid_owners,
                --s.outside_diam, 
                s.blocked_cond,  c.createddate,  du.NAME create_user, c.lastmodifieddate,  dum.NAME modified_user,
                mcl.total_length, total_sections, 
                sl.name start_loc, sl.locationid start_locid,
                el.name end_loc, el.locationid end_locid
              from cable c
              left join (select min(cl.sequence) min_cl, max(cl.sequence) max_cl, sum(cl.SECTIONLENGTH) total_length, count(*) total_sections, CBLC2CABLE cableid
                        FROM CABLELOCATION cl 
                        group by CBLC2CABLE) mcl on c.cableid = mcl.cableid
              left join CABLELOCATION scl on scl.CBLC2CABLE = c.cableid and scl.sequence=mcl.min_cl
              left join location_o sl on scl.CBLC2LOCATION = sl.LOCATIONID
              left join CABLELOCATION ecl on ecl.CBLC2CABLE = c.cableid and ecl.sequence=mcl.max_cl
              left join location_o el on ecl.CBLC2LOCATION = el.LOCATIONID
              join SATTAB_CABLE_COPPER s on s.cableid = c.cableid
              join cabletype ct on ct.CABLETYPEID = c.cable2cabletype  
              left join DIMUSER du on c.CREATEDBY2DIMUSER = du.DIMUSERID
              left join DIMUSER dum on c.LASTMODIFIEDBY2DIMUSER = dum.DIMUSERID
              join PROVISIONSTATUS ps on c.cable2PROVISIONSTATUS = ps.PROVISIONSTATUSID
              left join enumeration e1 on e1.fieldname = 'FEATURE1' 
                      and e1.tablename ='SATTAB_CABLE'  
                      and s.feature1 = e1.sequence
                left join enumeration e2 on e2.fieldname = 'FEATURE2'
                      and e2.tablename ='SATTAB_CABLE'  
                      and s.feature2 = e2.sequence
                left join enumeration e3 on e3.fieldname = 'FEATURE3'
                      and e3.tablename ='SATTAB_CABLE'  
                      and s.feature3 = e3.sequence
                left join enumeration e4 on e4.fieldname = 'CONDUCTOR_TYPE'
                      and e4.tablename ='SATTAB_CABLE'  
                      and s.conductor_type = e4.sequence
                left join enumeration e5 on e5.fieldname = 'OWNER'
                      and e5.tablename ='SATTAB_CABLE'  
                      and s.owner = e5.sequence
            --    left join enumeration e6 on e6.fieldname = 'RESPONSIBILITY'
            --          and e6.tablename ='SATTAB_CABLE'  
            --          and s.responsibility = e6.sequence
                left join enumeration e7 on e7.fieldname = 'SERVICE'
                      and e7.tablename ='SATTAB_CABLE'  
                      and s.service = e7.sequence
              where c.cableid =:id
			                    			",
            'file' => '../cfg/hp_templates/cable_copper.html',
            'header' => '<link rel="stylesheet" type="text/css" href="../css/general.css">'
         )
      )
   ),
   ////////////////////////////////////////////////////////////////////////////////////////////////       
   'cond' => array(
      'psql' => "select * from CABLECONDUCTOR c where c.CABLECONDUCTORID = :id",
      'switch_sql' => "select 'COND' template from dual",
      'templates' => array(
         'COND' => array(
            'sql' => "select cn.* ,
    cct.name type,
    ls.name start_location,
    ls.locationid start_locid,
    le.locationid end_locid,
    le.name end_location,
    c.NAME cable,
    ct.name cable_type,
    du.NAME create_user,
    dum.NAME modified_user,
    ps.NAME PROVISIONSTATUS,
    ls.locationid start_locid,
    le.locationid end_locid,
   sn.name start_node, sn.nodeid snid,
   sp.name start_port, sp.portid spid,
   en.name end_node, en.nodeid enid,
   ep.name end_port, ep.portid epid,
   ll.name link, ll.linkid,
   fr.name fiber_route, fr.circuitid frid,
   c2.name over_circuit, c2.circuitid overid
   
    
from CABLECONDUCTOR cn
join CABLECONDUCTORTYPE cct on cn.CCND2CABLECONDUCTORTYPE = cct.CABLECONDUCTORTYPEID
join CABLECONDUCTORGROUP ccg on ccg.CABLECONDUCTORGROUPID =cn.CCND2CABLECONDUCTORGROUP
join cableLOCATION cls on ccg.CCGR2STARTCABLELOCATION = cls.CABLELOCATIONID
join LOCATION_o ls on cls.CBLC2LOCATION = ls.LOCATIONID
join cableLOCATION cle on ccg.CCGR2ENDCABLELOCATION = cle.CABLELOCATIONID
join LOCATION_o le on cle.CBLC2LOCATION = le.LOCATIONID
join CABLE c on c.CABLEID = cn.CABLECONDUCTOR2CABLE
join CABLETYPE ct on c.CABLE2CABLETYPE = ct.CABLETYPEID
join DIMUSER du on cn.CREATEDBY2DIMUSER = du.DIMUSERID
join DIMUSER dum on cn.LASTMODIFIEDBY2DIMUSER = dum.DIMUSERID
join PROVISIONSTATUS ps on cn.CCND2PROVISIONSTATUS = ps.PROVISIONSTATUSID
left join port sp on cn.ccnd2startport = sp.portid
left join node_o sn on sp.port2node = sn.nodeid
left join port ep on cn.ccnd2endport = ep.portid
left join node_o en on ep.port2node = en.nodeid
left join cableconductorlinkmapping ccm on cn.cableconductorid = ccm.cclm2cableconductor
left join link_o ll on ccm.cclm2link = ll.linkid
left join linkcircuit_o lc on ccm.cclm2link = lc.linkcircuit2link
left join circuit_o fr on lc.linkcircuit2circuit = fr.circuitid
left join circuitcircuit_o cc on fr.circuitid = cc.uses2circuit
left join circuit_o c2 on cc.usedby2circuit = c2.circuitid
where cn.CABLECONDUCTORID = :id",
            'file' => '../cfg/hp_templates/cond.html',
            'header' => '<link rel="stylesheet" type="text/css" href="../css/general.css">'
         )
      )
   ),
   //////////////////////////////////////////////////////////////////////////////////////////////////
   'num' => array(
      'psql' => "select t.*, 
						nt.name type,
						du.name create_user,
						dum.name modified_user,
						ps.name PROVISIONSTATUS,
						case when not pn.name is null then pn.name||' ('||pnt.name||')' end parent_number,
						case when not rn.name is null then rn.name||' ('||rnt.name||')' end root_number,
						case when not unn.name is null then unn.name||' ('||unnt.name||')' end unique_number,
						 nt.TABLENAME sattab
					from dimnumber_o t 
					left join dimnumbertype nt on t.dimnumber2dimnumbertype = nt.dimnumbertypeid
					left join DIMUSER du on t.CREATEDBY2DIMUSER = du.DIMUSERID
					left join DIMUSER dum on t.LASTMODIFIEDBY2DIMUSER = dum.DIMUSERID
					left join PROVISIONSTATUS ps on t.DIMNUMBER2PROVISIONSTATUS = ps.PROVISIONSTATUSID	
					left join dimnumber_o pn on t.PARENTDIMNUMBER2DIMNUMBER = pn.dimnumberid
					left join dimnumbertype pnt on pn.dimnumber2dimnumbertype = pnt.dimnumbertypeid
					left join dimnumber_o rn on t.DIMNUMBER2ROOTDIMNUMBER = rn.dimnumberid
					left join dimnumbertype rnt on rn.dimnumber2dimnumbertype = rnt.dimnumbertypeid
					left join dimnumber_o unn on t.DIMNUMBER2UNIQUEDIMNUMBER = unn.dimnumberid
					left join dimnumbertype unnt on unn.dimnumber2dimnumbertype = unnt.dimnumbertypeid
					where t.dimnumberid=:id",
      'links' => array(
         'PARENT_NUMBER' => array('id' => 'PARENTDIMNUMBER2DIMNUMBER', 'prefix' => 'num'),
         'ROOT_NUMBER' => array('id' => 'DIMNUMBER2ROOTDIMNUMBER', 'prefix' => 'num'),
         'UNIQUE_NUMBER' => array('id' => 'DIMNUMBER2UNIQUEDIMNUMBER', 'prefix' => 'num')
      ),
      'sattab' => array(
         'sql' => "select * from :table where dimnumberid = :id"
      ),
      'switch_sql' => "select case when DIMNUMBER2DIMNUMBERTYPE = 1900000040 then 'TEL_NUMBER' 
                                       when DIMNUMBER2DIMNUMBERTYPE = 1900000003 then 'CABLE_LINE' 
                                     end template from dimnumber_o where dimnumberid = :id",
      'templates' => array(
         'TEL_NUMBER' => array(
            'sql' => "
				SELECT   n.DIMNUMBERID, n.name numbername,
						 --E.VALUE NUMBER_TYPE_NAME,
                         n.subtype NUMBER_TYPE_NAME,
						 P.VALUE  TELBLOCKNAME, f.NAME LAC,
						 k.name cityname, 
						 M.VALUE ORGANIZATIONNAME,         						 
						 D.NAME PROVISIONSTATUSNAME,
						 b.cl_id, b.SIEBEL_ID sat_siebel_id,
						 s.objectid subs_id,  b.valid_to_date,
						 B.UPDATER LASTMODIFIEDUSERNAME,
						 b.updater modify_userid, 
						 c.VALUE BEAUTLYNAME,
						 n.LASTMODIFIEDDATE,
						 B.WO_NUM,  n.NOTES,
						 B.REZERVED_SERVICE RESERVED_SERVICE,
						 B.STATE_CHANGE_DATE,
						 B.RESERVATOR, b.siebel_id,
                                                 h.valuefrom||'-'||h.valueto as parentdimnumber,
						 s.serviceid, s.name servicename, st.point_a , s.substatus SERVICESTATE, 						 
						 sb.subscriberid, nvl(sb.name, 'No Number in BIS') subscribername, sb.description SUBSCRIBER, sb.relativename as OKPO,  sb.alias1 as segm,
       					sb.alias2 clas, sb.objectid ls, sb.subtype company, sb.notes as sm, sbsat.dms_id, sbsat.siebel_id                
				FROM   dimnumber_o n 
				left join sattab_telnumber_o b on n.DIMNUMBERID = B.DIMNUMBERID
				left join enumeration c on B.BEAUTLY = c.SEQUENCE
													   AND c.tablename = 'SATTAB_TELNUMBER'
													   AND c.FIELDNAME = 'BEAUTLY'
				left join enumeration e on B.NUMBER_TYPE=E.SEQUENCE
													   and E.TABLENAME='SATTAB_TELNUMBER'
													   and e.FIELDNAME='NUMBER_TYPE'
				left join dimnumber_o h on n.PARENTDIMNUMBER2DIMNUMBER = H.DIMNUMBERID
				left join status_m d on n.DIMNUMBER2PROVISIONSTATUS=d.STATUSID
				left join dimnumber f on h.PARENTDIMNUMBER2DIMNUMBER = f.DIMNUMBERID
				left join numberobject_o g on f.DIMNUMBERID=G.NUMBEROBJECT2NUMBER
													and G.NUMBEROBJECT2RELATION=2002
				left join location_o k on G.NUMBEROBJECT2OBJECT=k.locationid
				left join sattab_telblock_o l on n.PARENTDIMNUMBER2DIMNUMBER=L.DIMNUMBERID
				left join enumeration m  on L.ORGANIZATION=m.SEQUENCE
													   and m.TABLENAME='SATTAB_TELBLOCK'
													   and lower(m.FIELDNAME)=lower('Organization')
				left join enumeration p on L.SERVICE_TYPE=P.SEQUENCE
													   and P.TABLENAME='SATTAB_TELBLOCK'
													   and lower(P.FIELDNAME)=lower('Service_Type')
				left join numberobject n2o on n.DIMNUMBERID=n2o.NUMBEROBJECT2NUMBER
													   and n2o.NUMBEROBJECT2RELATION=1900000076                                       
				left join service_o s on n2o.NUMBEROBJECT2OBJECT=s.SERVICEID
				left join subscriber_o sb on sb.subscriberid = s.service2subscriber
				left join sattab_sevice_b2b_o st on st.serviceid = s.serviceid
				left join sattab_subscriber_o sbsat on sbsat.subscriberid = sb.subscriberid
				WHERE  n.DIMNUMBERID = :id         
			   ",
            'file' => '../cfg/hp_templates/tel_number.html',
            'header' => '<link rel="stylesheet" type="text/css" href="../css/general.css">'
         ),
         'CABLE_LINE' => array(
            'db' => 'cramer_admin',
            'sql' => "
				select n.dimnumberid, n.subtype, n.name, n.description asup_address, n.alias1, n.alias2, st.lenght, e4.value ctype, n.objectid, 
                       st.cable_type, st.conductors, st.asup_state, st.asup_start_date, n.notes,
                       e1.value ppo, e2.value avr, e3.value opp, e5.value budget, e6.value gpo_maint, st.gpo_build,
                       	n.createddate, du.name create_user, n.lastmodifieddate, dum.name modified_user,
						ps.name PROVISIONSTATUS, 
                        w.id, w.web_giS_ID, W.NAME WEBGISNAME, W.LENGTH_M, W.FIBERS, w.PLAN_DATE, w.STATUS, w.BUDGET WEBGISBUDGET, w.COMMENTS, w.COUNTRY_REGION, w.MODIFY_USER, w.MODIFY_DATE,
                        w.wo, st.changeid, st.zp_asup,   st.TO_START_DATE, st.tu_order_date, st.tu_receive_date, st.ds_sign_date, st.DS_PLAN_FINISH_DATE
                from dimnumber_o n 
                join SATTAB_NUM_CABLE_LINE_o st on st.dimnumberid = n.dimnumberid
                left join enumeration e1 on e1.TABLENAME =  'SATTAB_NUM_CABLE_LINE' and e1.FIELDNAME = 'PPO'
                                            and e1.SEQUENCE = st.ppo
                left join enumeration e2 on e2.TABLENAME =  'SATTAB_NUM_CABLE_LINE' and e2.FIELDNAME = 'AVR'
                                            and e2.SEQUENCE = st.avr
                left join enumeration e3 on e3.TABLENAME =  'SATTAB_NUM_CABLE_LINE' and e3.FIELDNAME = 'OPP'
                                            and e3.SEQUENCE = st.opp
                left join enumeration e4 on e4.TABLENAME =  'SATTAB_NUM_CABLE_LINE' and e4.FIELDNAME = 'TYPE'
                                            and e4.SEQUENCE = st.type
                left join enumeration e5 on e5.TABLENAME =  'SATTAB_NUM_CABLE_LINE' and e5.FIELDNAME = 'BUDGET'
                                            and e5.SEQUENCE = st.budget
                left join enumeration e6 on e6.TABLENAME =  'SATTAB_NUM_CABLE_LINE' and e6.FIELDNAME = 'GPO_MAINT'
                                            and e6.SEQUENCE = st.gpo_maint 
                left join DIMUSER du on n.CREATEDBY2DIMUSER = du.DIMUSERID
				left join DIMUSER dum on n.LASTMODIFIEDBY2DIMUSER = dum.DIMUSERID
				left join PROVISIONSTATUS ps on n.DIMNUMBER2PROVISIONSTATUS = ps.PROVISIONSTATUSID 
                left  join cramer2gis.v_cablesegments_planning@webgis w on remove_date is null and Regexp_Replace(w.web_gis_id, '\W') = Regexp_Replace(n.name, '\W')                 
                where DIMNUMBER2DIMNUMBERTYPE  = 1900000003
                      and  n.DIMNUMBERID = :id       
			   ",
            'file' => '../cfg/hp_templates/cable_line.html',
            'header' => '<link rel="stylesheet" type="text/css" href="../css/general.css">'
         )
      )
   ),
   //////////////////////////////////////////////////////////////////////////      
   'doc' => array(
      'psql' => "select d.documentid, d.name, d.fullname, d.relativename, d.alias1, d.alias1, d.alias2, d.objectid,
					   d.subtype, d.description, d.notes, dt.name documenttype, d.createddate, du.name create_user,
						 d.lastmodifieddate, dum.name modified_user, d.details, 
						 ps.name PROVISIONSTATUS, d.document2documenttype,   d.parentdocument2document, 
						 d.document2provisionstatus, d.document2functionalstatus,
                         dt.TABLENAME sattab
					  from document d 
					  join documenttype_m dt on dt.documenttypeid = d.document2documenttype
					left join DIMUSER du on d.CREATEDBY2DIMUSER = du.DIMUSERID
					left join DIMUSER dum on d.LASTMODIFIEDBY2DIMUSER = dum.DIMUSERID
					join PROVISIONSTATUS ps on d.DOCUMENT2PROVISIONSTATUS = ps.PROVISIONSTATUSID					
					where d.documentid=:id",
      'sattab' => array(
         'sql' => "select * from :table where documentid = :id"
      )
   )
);
