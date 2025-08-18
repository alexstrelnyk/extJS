<?

function parseColorColumns($col, $label = false)
{
    if (!$label) {
        $label = explode('.', $col)[1];
    }
    $style = 'padding: 5px;height: 13px;';
    $res = "CASE 
		WHEN k.severity = 5 THEN '<div style=''" . $style . "background-color: #f5abab''>' || TO_CHAR($col) || '</div>'
		WHEN k.severity = 4 THEN '<div style=''" . $style . "background-color: #f5c390''>' || TO_CHAR($col) || '</div>'
		WHEN k.severity = 3 THEN '<div style=''" . $style . "background-color: #f1f5b8''>' || TO_CHAR($col) || '</div>'
		WHEN k.severity = 2 THEN '<div style=''" . $style . "background-color: #b8c1f5''>' || TO_CHAR($col) || '</div>'
		WHEN k.severity = 1 THEN '<div style=''" . $style . "background-color: #d3b5f7''>' || TO_CHAR($col) || '</div>'
		WHEN k.severity = 0 THEN '<div style=''" . $style . "background-color: #b2eda6''>' || TO_CHAR($col) || '</div>'
		ELSE TO_CHAR(k.severity)
	END AS $label";

    return $res;
}

$cfg = array(
    'CRAMER_SITES_STATUS_REPORT' => array(
        'css' => array('./css/common.css'),
        'template' => "<br>
    			<center><b>Sites status report</b></center>
    			<br>
                        <table id=form border=0 align=center>
                        <tr>
							<td>Область: </td><td> <div id=pCity ksType=combobox></div> </td>
                        </tr>
                        <tr>
							<td>Cайт: </td><td> <div id=pSite ksType=combobox></div> </td>
                        </tr>
                        <tr>
                            <td>Проблема: </td><td> <div id=problem ksType=combobox></div> </td>
                        </tr>
                        <tr>
                            <td>Severity: </td><td> <div id=severity ksType=combobox></div> </td>
                        </tr>
                        <tr>
                            <td align=right colspan=4> <div id=b_exec ksType=button></div> </td>
                        </tr>
                    </table>
            <br>
            <center><div id=mainTable ksType=table table_style=js></div></center>
            ",
        'ext' => "document.loadCfg['autoLoad'] = false;
                 document.ext['mainTable']={viewConfig: { columns:[{ header:'NODE', dataIndex:'NODE', width:150}], forceFit : true}};
        ",
        'combo' => array(
            'pCity' => array(
                'db' => 'cramer_admin',
                'sql' => "select province id, fullname as name from location_o where name LIKE '%obla%'
                        	union select null, 'All' from dual
                    		order by name"
            ),
            'pSite' => array(
                'db' => 'reporter',
                'sql' => "select n.name as id,n.name from mv_cramer_node n where n.type like 'RBS%'
AND ROWNUM < 20
ORDER BY n.name
"
            ),
            'problem' => array(
                'db' => 'cramer_admin',
                'sql' => "
					SELECT 1 AS id, 'сайт не працює' AS name FROM dual
					UNION ALL SELECT 2, 'проблема з живленням' FROM dual
					UNION ALL SELECT 3, 'проблема з температурою' FROM dual
					UNION ALL SELECT 4, 'працює генератор' FROM dual
				"
            ),
            'severity' => array(
                'db' => 'cramer_admin',
                'sql' => "
					SELECT 
						LEVEL - 1 AS id,
						TO_CHAR(LEVEL - 1) AS name
					FROM dual
					CONNECT BY LEVEL <= 6
					ORDER BY id
				"
            ),

        ),
        'table' => array(
            'mainTable' => array(
                'show_header' => true,
                'db' => 'reporter',
                'sql' => "
SELECT 
	" . parseColorColumns('k.severity') . ",
	" . parseColorColumns('l.region') . ",
	" . parseColorColumns('l.name', 'location') . ",
	" . parseColorColumns('NVL(k.nodealias, k.node)', 'node') . ",
	" . parseColorColumns('k.specificproblem') . ",
	" . parseColorColumns('k.summary') . ",
	" . parseColorColumns('k.firstoccurrence') . ",
	" . parseColorColumns('k.ttid') . ",
	" . parseColorColumns('k.physicalcard') . ",
	" . parseColorColumns('at.CONVERSION', 'alarmtype') . ",
	" . parseColorColumns('ac.CONVERSION', 'alarmcode') . ",
	" . parseColorColumns('ar.CONVERSION', 'alarmrange') . "

FROM REPORTER_STATUS4CMS k
JOIN mv_cramer_location l 
    ON k.location = l.name
LEFT JOIN reporter_conversions at 
    ON at.value = k.alarmtype AND LOWER(at.column_name) = 'alarmtype'
LEFT JOIN reporter_conversions ac 
    ON ac.value = k.alarmtype AND LOWER(ac.column_name) = 'alarmcode'
LEFT JOIN reporter_conversions ar 
    ON ar.value = k.alarmtype AND LOWER(ar.column_name) = 'alarmrange'
WHERE 
    (l.region = '<pCity>' OR '<pCity>' IS NULL OR '<pCity>' = '') 
    AND (k.nodealias = '<pSite>' OR '<pSite>' IS NULL OR '<pSite>' = '')
    AND (k.severity = '<severity>' OR '<severity>' IS NULL OR '<severity>' = '')
    AND (
        '<problem>' IS NULL OR '<problem>' = '' OR '<problem>' = '0'
        OR (
            '<problem>' = '1' 
            AND k.specificproblem IN (
                'SITE ABIS CONTROL LINK BROKEN',
                'LINK BETWEEN OMM AND NE BROKEN',
                'NE Is Disconnected',
                'CSL Fault'
            )
        )
        OR (
            '<problem>' = '2' 
            AND k.specificproblem = 'BTS EXTERNAL FAULT'
            AND (
                LOWER(k.summary) LIKE 'mains%' OR
                LOWER(k.summary) LIKE 'high%' OR
                LOWER(k.summary) LIKE 'rectif%' OR
                LOWER(k.summary) LIKE 'load%'
            )
        )
        OR (
            '<problem>' = '3' 
            AND k.specificproblem LIKE 'TP%'
        )
        OR (
            '<problem>' = '4' 
            AND (
                k.specificproblem LIKE 'DGA%' 
                OR k.summary LIKE 'DIESEL%'
            )
        )
    )

	--FETCH FIRST 10 ROWS ONLY
					"
            )
        )
    ),
    'CRAMER_FREE_SPLITER_PORT_SEARCH' => array(
        'css' => array('./css/common.css'),
        'template' => "<br>
    			<center><b>Splitter port switch</b></center> 
    			<br>
                        <table id=form border=0 align=center>
                        <tr>
                            <td>Назва сплітеру: </td><td><div id=pNode ksType=textfield></div></td>
                        </tr>
                        <tr>
                            <td>Назва ТКД: </td><td> <div id=pAdr ksType=textfield></div> </td>
                        </tr>
                        <tr>
                            <td>Bільнi порти: </td><td> <div id=ports ksType=combobox></div> </td>
                        </tr>
                        <tr>
                            <td align=right colspan=4> <div id=b_exec ksType=button></div> </td>
                        </tr>
                    </table>
            <br>
            <center><div id=mainTable ksType=table table_style=js></div></center>
            ",
        'ext' => "document.loadCfg['autoLoad'] = false;
                 document.ext['pNode']={width:200};
                 document.ext['pAdr']={width:200};
                 document.ext['ports']={width:200,value: '0'};
                 document.ext['mainTable']={viewConfig: { columns:[{ header:'NODE', dataIndex:'NODE', width:150}], forceFit : true}};
        ",
        'combo' => array(
            'ports' => array(
                'db' => 'cramer_admin',
                'sql' => "
					SELECT 0 AS id, '0' AS name FROM dual
					UNION ALL SELECT 1, '1' FROM dual
					UNION ALL SELECT 2, '2' FROM dual
					UNION ALL SELECT 3, '3' FROM dual
					UNION ALL SELECT 4, '4' FROM dual
					UNION ALL SELECT 5, '5' FROM dual
					UNION ALL SELECT 6, '6' FROM dual
					UNION ALL SELECT 7, '7' FROM dual
					UNION ALL SELECT 8, '8' FROM dual
					UNION ALL SELECT 9, '9' FROM dual
					UNION ALL SELECT 10, '10' FROM dual
				"
            ),

        ),
        'table' => array(
            'mainTable' => array(
                'show_header' => true,
                'db' => 'cramer_admin',
                'sql' => "SELECT
					  n.name AS node_name,
					  l.name AS location_name,
					  COUNT(DISTINCT p.portid) AS ports_count,
					  COUNT(DISTINCT s.serviceid) AS services_count

					FROM CRAMER.port_o p
					JOIN CRAMER.node_o n ON n.nodeid = p.port2node
					  AND n.node2nodetype = 1900000189

					LEFT JOIN CRAMER.port_o pp ON pp.parentport2port = p.portid

					LEFT JOIN CRAMER.CIRCUIT_O c ON c.CIRCUIT2STARTPORT = pp.portid
												 OR c.CIRCUIT2ENDPORT = pp.portid

					LEFT JOIN CRAMER.serviceobject_o so ON so.SERVICEOBJECT2OBJECT = c.circuitid
					  AND so.SERVICEOBJECT2DIMOBJECT = 3
					  AND so.SERVICEOBJECT2RELATION = 1800000001

					LEFT JOIN CRAMER.service_o s ON s.serviceid = so.serviceobject2service
					  AND s.service2servicetype IN (1900000009, 1900000011)

					LEFT JOIN CRAMER.location_o l ON l.locationid = n.node2location

					WHERE p.parentport2port IS NULL
					  AND p.port2porttype = 1900000061

					  AND (
						'<pNode>' IS NULL OR '<pNode>' = '' OR '<pNode>' = '0'
						OR LOWER(n.name) LIKE LOWER('%<pNode>%')
					  )

					  AND (
						'<pAdr>' IS NULL OR '<pAdr>' = '' OR '<pAdr>' = '0'
						OR LOWER(l.name) LIKE LOWER('%<pAdr>%')
					  )

					GROUP BY
					  n.name,
					  l.name

					HAVING 
					  COUNT(DISTINCT c.circuitid) > 0
					  AND COUNT(DISTINCT p.portid) <= COUNT(DISTINCT s.serviceid) + TO_NUMBER(nvl('<ports>',0))

					ORDER BY services_count DESC
					--FETCH FIRST 100 ROWS ONLY
					"
            )
        )
    ),
    'CRAMER_FTTB_SWITCH_SEARCH' => array(
        'css' => array('./css/common.css'),
        'template' => "<br>
    			<center><b>Пошук FTTB switch</b></center>
    			<br>
                        <table id=form border=0 align=center>
                        <tr>
                            <td>Імя свіча, MAC, IP, SN: </td><td><div id=pNode ksType=textfield></div></td>
                            <td>Тип пристрою: </td><td> <div id=pType ksType=combobox></div> </td>
                        </tr>
                        <tr>
                            <td>Назва ТКД, адреса, HouseID: </td><td> <div id=pAdr ksType=textfield></div> </td>
                            <td>Місто: </td><td> <div id=pCity ksType=combobox></div> </td>
                        </tr>
                        <tr>
                            <td align=right colspan=4> <div id=b_exec ksType=button></div> </td>
                        </tr>
                    </table>
            <br>
            <center><div id=mainTable ksType=table table_style=js></div></center>
            ",
        'ext' => "document.loadCfg['autoLoad'] = false;
                 document.ext['pNode']={width:200};
                 document.ext['pType']={width:200,value: ''};
                 document.ext['pAdr']={width:200};
                 document.ext['pCity']={width:200,value: ''};
                 document.ext['mainTable']={viewConfig: { columns:[{ header:'NODE', dataIndex:'NODE', width:150}], forceFit : true}};
        ",
        'combo' => array(
            'pCity' => array(
                'db' => 'cramer_admin',
                'sql' => "select locationid id, name from location_o where location2locationtype = 1800000012
                        	union select 0, '<empty>' from dual
                    		order by name"
            ),
            'pType' => array(
                'db' => 'cramer_admin',
                'sql' => "select nodetypeid id, name from nodetype_m where nodetypeid in 
                             (1900000162,1900000151,1900000141,1900000089,1900000085,1900000075,1900000074,1900000073,1900000060,1900000175,1900000182,1900000183,1900000109)
                                   union select 0, '<empty>' from dual"
            )
        ),
        'table' => array(
            'mainTable' => array(
                'show_header' => true,
                'db' => 'cramer_admin',
                'sql' => "
			select 
			('<a href=" . ($_SERVER['HTTPS'] == "on" ? 'https' : 'http') . "://msu2.kyivstar.ua/Integration/Cramer/Navigator/content/homepage.php?page=homepage&el=node_'||n.nodeid||' target=_blank>'||regexp_substr(n.name, '[^/]+',1,1)||'</a>') node,
--            ('<a href=''#'' onclick=\"if (window.top==window) {window.open(''http://msu2.kyivstar.ua/Integration/Cramer/Navigator/content/homepage.php?page=homepage&&el=node_'||n.nodeid||''');} else {window.top.opentab('''||regexp_substr(n.name, '[^/]+',1,1)||''', ''node_'||n.nodeid||''');};\">'||regexp_substr(n.name, '[^/]+',1,1)||'</a>') node,
			regexp_substr(n.name, '[^/]*$') nodetype,
			s.ip_address,
            substr(s.vlan,1,2)||'xx' vlans,
            case when s.ERPS_RING_ID is null then 'STP, priority='||s.STP_PRIORITY
            else 'ERPS, ring_id='||ERPS_RING_ID||decode(ERPS_OWNER_PORT,'1',' (Owner)','') end ring_protocol,
            n.substatus as hop,
			l.description address,
			l.alias2 gpo,
            fs.name func_status,
			regexp_substr(n.alias1, '[^/]+',1,1) as DS,
            p.name DS_Port,
			s.mac_address,
			s.serial_number,
			--l1.name city,
            mm.project_code as mustang_location,
			vft.TT tt,
			vft.KPI kpi,
            l.name tkd
			from node_o n
            left join port_vw p on  p.portid = (n.alias2+0)
		join location_o l on n.node2location = l.locationid
		join location_o l1 on l.location2parentlocation = l1.locationid
		
		left join V_FTTB_TKD_KPI30 vft on vft.locationid=l.locationid
		
		join (select erps_owner_port,ip_address,vlan,erps_ring_id,stp_priority,mac_address,serial_number,nodeid,hostname from sattab_os6250_24m_node_o
			       union select erps_owner_port,ip,vlanid,erps_ring_id,stp_priority,mac,serial,nodeid,sysname from sattab_gt_nodes2_o) s on s.nodeid = n.nodeid
		left join PROVISIONSTATUS ps on n.NODE2PROVISIONSTATUS = ps.PROVISIONSTATUSID
            left join status fs on n.node2functionalstatus = fs.statusid
            left join KS_MUSTANG_SERIAL_NUMBERS mm on mm.serial_number=s.serial_number
			where l.location2locationtype = 1900000004 
				and (n.NODE2NODETYPE='<pType>'
                                      or (n.NODE2NODETYPE in (1900000162,1900000151,1900000141,1900000089,1900000085,1900000075,1900000074,1900000073,1900000060,1900000175,1900000183,1900000182,1900000109) 
						and ('<pType>' is null or '<pType>'=0)))
				and (n.subtype<>'Precision' or n.subtype is null)
				and (l1.locationid = '<pCity>' or  '<pCity>' is null or '<pCity>'=0 ) 
				and (lower(l.description) like lower('%<pAdr>%') 
					 or lower(l.name) like lower('%<pAdr>%')
                                         or lower(l.zip) like lower('%<pAdr>%')
					 or '<pAdr>' is  null) 
			     and ( '<pNode>' is null  
					 or lower(n.name) like lower('%<pNode>%')  
					 or s.ip_address like ('%<pNode>%')
					 or lower(s.mac_address) like lower('%<pNode>%')
					 or lower(s.serial_number) like lower('%<pNode>%')
					 or lower(s.hostname) like lower('%<pNode>%')
					 )
				and ('<pCity>' is not null or '<pAdr>' is not null 
					or '<pType>' is not null or '<pNode>' is not null)
			order by n.name
"
            )
        )
    ),

    'CRAMER_FTTB_SWITCH_BIS' => array(
        'css' => array('./css/common.css'),
        'template' => "<br>
    			<center><b>Пошук FTTB switch в розрізі клієнтів</b></center>
    			<br>
                        <table id=form border=0 align=center>
                        <tr>
                            <td>Ім&rsquo;я свіча, IP: </td><td><div id=pNode ksType=textfield></div></td>
                            <td>Тип комутатора: </td><td> <div id=pType ksType=combobox></div> </td>
                        </tr>
                        <tr>
                            <td>Назва ТКД: </td><td> <div id=pLoc ksType=textfield></div> </td>
                            <td>Місто: </td><td> <div id=pCity ksType=combobox></div> </td>
                        </tr>
                        <tr>
                            <td align=right colspan=4> <div id=b_exec ksType=button></div> </td>
                        </tr>
                    </table>
            <br>
            <center><div id=mainTable ksType=table table_style=js></div></center>
            ",
        'ext' => "document.loadCfg['autoLoad'] = false;
                 document.ext['pNode']={width:200};
                 document.ext['pType']={width:200,value: ''};
                 document.ext['pLoc']={width:200};
                 document.ext['pCity']={width:200,value: ''};
				 document.ext['b_exec']={text: 'Пошук'};
        ",
        'combo' => array(
            'pCity' => array(
                'db' => 'cramer_admin',
                'sql' => "select locationid id, name from location_o where location2locationtype = 1800000012 and not location2parentlocation in (5553)
                        	union select 0, '' from dual
                    		order by name"
            ),
            'pType' => array(
                'db' => 'cramer_admin',
                'sql' => "select nodetypeid id, name from nodetype_m where nodetypeid in 
                             (1900000162,1900000151,1900000141,1900000089,1900000085,1900000075,1900000074,1900000073,1900000060,1900000175,1900000183,1900000182,1900000109)
                                   union select 0, '' from dual"
            )
        ),
        'table' => array(
            'mainTable' => array(
                'show_header' => true,
                'db' => 'cramer_admin',
                'sql' => "
			select 
			regexp_substr(n.name, '[^/]+',1,1) node,
			regexp_substr(n.name, '[^/]*$') nodetype,
			s.ip_address,
            --substr(s.vlan,1,2)||'xx' vlans,
            --case when s.ERPS_RING_ID is null then 'STP, priority='||s.STP_PRIORITY
            --else 'ERPS, ring_id='||ERPS_RING_ID||decode(ERPS_OWNER_PORT,'1',' (Owner)','') end ring_protocol,
			l.description address,
			l1.name sity,
			l2.name oblast,
			--l.alias2 gpo,
            fs.name func_status,
			--regexp_substr(n.alias1, '[^/]+',1,1) as DS,
			--s.mac_address,
			--s.serial_number,
            l.name tkd,
			cir.fttb fttb_client,
			cir.b2b b2b_client
			from node_o n
			join location_o l on n.node2location = l.locationid
			join location_o l1 on l.location2parentlocation = l1.locationid
			join location_o l2 on l1.location2parentlocation = l2.locationid and not l1.location2parentlocation in (5553)
			join (select erps_owner_port,ip_address,vlan,erps_ring_id,stp_priority,mac_address,serial_number,nodeid,hostname from sattab_os6250_24m_node_o
			       union select erps_owner_port,ip,vlanid,erps_ring_id,stp_priority,mac,serial,nodeid,sysname from sattab_gt_nodes2_o) s on s.nodeid = n.nodeid
			left join PROVISIONSTATUS ps on n.NODE2PROVISIONSTATUS = ps.PROVISIONSTATUSID
            left join status fs on n.node2functionalstatus = fs.statusid
            left join (select c.circuit2startnode,count(case when c.subtype='FTTB' then 1 end) fttb, count(case when c.subtype='FTTB4B2B_manual' then 1 end) b2b from circuit_o c
            join SERVICEOBJECT_O so on so.serviceobject2object = c.circuitid  and so.SERVICEOBJECT2DIMOBJECT = 3
            where c.circuit2circuittype=1900000022 group by c.circuit2startnode) cir on cir.circuit2startnode=n.nodeid
			where l.location2locationtype = 1900000004
                and n.node2functionalstatus=1800000042
				and (n.NODE2NODETYPE='<pType>'
                                      or (n.NODE2NODETYPE in (1900000162,1900000151,1900000141,1900000089,1900000085,1900000075,1900000074,1900000073,1900000060,1900000175,1900000183,1900000182,1900000109) 
						and ('<pType>' is null or '<pType>'=0)))
				and (n.subtype<>'Precision' or n.subtype is null)
				and (l1.locationid = '<pCity>' or  '<pCity>' is null or '<pCity>'=0 ) 
				and (lower(l.name) like lower('%<pLoc>%')
					 or '<pAdr>' is  null) 
			     and ( '<pNode>' is null  
					 or lower(n.name) like lower('%<pNode>%')  
					 or s.ip_address like ('%<pNode>%')
					 or lower(s.hostname) like lower('%<pNode>%')
					 )
				and ('<pCity>' is not null or '<pLoc>' is not null 
					or '<pType>' is not null or '<pNode>' is not null)
			order by n.name
				"
            )
        )
    ),
    'CRAMER_FTTB_DS_PORT' => array(
        'css' => array('./css/common.css'),
        'template' => "<br>
    			<center><b>Звіт по портах DS</b></center>
            <br>
            <center><div id=mainTable ksType=table class=commonTable></div></center>
            ",
        'table' => array(
            'mainTable' => array(
                'show_header' => true,
                'db' => 'cramer_admin',
                'sql' => "select w.tkd RING,
 listagg(regexp_substr(w.ds,'^[^/]+')||' ('||w.port||') ') WITHIN GROUP(order by w.tkd) INFO
 from (
   select substr(n.name, 0,16) tkd, p.name port, n.alias1 ds
     from node_o n
     join (select nodeid,ip_address from SATTAB_OS6250_24M_NODE_O union select nodeid,ip from SATTAB_GT_NODES2_O) s on s.nodeid=n.nodeid
     left join port_vw p on p.portid=(n.alias2+0)
   where n.node2nodetype in (1900000162,1900000151,1900000141,1900000089,1900000085,1900000075,1900000074,1900000073,1900000060,1900000175,1900000183,1900000182,1900000109)
     and n.NODE2FUNCTIONALSTATUS = 1800000042
     and (n.subtype is null or n.subtype <> 'Precision')
   group by substr(n.name,0,16), p.name, n.alias1
 ) w
group by w.tkd
having count(w.tkd)>2
"
            )
        )
    ),
    'CRAMER_FTTB_DS_BEE' => array(
        'css' => array('./css/common.css'),
        'template' => "<br>
            <center><b>Звіт по Beeline AGG ___90%</b></center>
            <br>
            <center><div id=mainTable ksType=table class=commonTable></div></center>
            ",
        'table' => array(
            'mainTable' => array(
                'show_header' => true,
                'db' => 'cramer_admin',
                'sql' => "select l.locationid,l.name,l.alias2,l.subtype,l.description,l.towncity,l.responsible,n.name node,l.notes from location_o l
                join node_o n on n.node2location=l.locationid and n.name like 'asw%'
                join (select nodeid from sattab_os6250_24m_node_o union select nodeid from sattab_gt_nodes2_o) s on s.nodeid=n.nodeid"
            )
        )
    ),
    'CRAMER_TKD_GPO' => array(
        'css' => array('./css/common.css'),
        'template' => "<br><center><font size=4><b> MDU TO GPO</font></center><br>
                    <center><div id=mainTable ksType=table class=commonTable></div></center>
",
        'table' => array(
            'mainTable' => array(
                'show_header' => true,
                'db' => 'cramer_admin',
                'sql' => "
                select vp.mdu_lot as lot,
                       reg.name region,
                       sreg.name oblast,
                       city.name city,
                       tkd.alias2 gpo,
                       tkd.relativename mdu,
                       count(distinct tkd.locationid) tkd_count,
                       count(*) switch_count
                from location_o tkd
                join location_o city on tkd.location2parentlocation = city.locationid
                join location_o sreg on city.location2parentlocation = sreg.locationid and sreg.locationid<>5553
                join location_o reg on sreg.location2parentlocation = reg.locationid
                join node_o n on tkd.locationid = n.node2location
                left join ks_vportal_fttb_tkd vp on vp.id_tkd=tkd.objectid
                where tkd.location2locationtype = 1900000004
                        and n.node2nodedef in
                           (1900000076, 1900000115, 1900000102, 1900000103, 1900000105,
                           1900000106, 1900000119, 1900000267, 1900000308, 1900000336, 1900000379,1900000398,1900000397,1900000404,1900000407,1900000417,1900000420)
                        and (n.subtype is null or n.subtype <> 'Precision')
                        and n.node2functionalstatus = 1800000042
                group by reg.name, sreg.name, city.name, tkd.alias2, tkd.relativename, vp.mdu_lot
                order by reg.name, sreg.name, city.name, tkd.alias2, tkd.relativename
                "
            )
        )
    ),
    'FTTB_MDU_ABON' => array(
        'css' => array('./css/common.css'),
        'template' => "<br>
            <center><b>Кількість абонентських сервісів по МДЮ</b></center>
            <br>
            <center><div id=mainTable ksType=table class=commonTable></div></center>
            ",
        'table' => array(
            'mainTable' => array(
                'show_header' => true,
                'db' => 'cramer_admin',
                'sql' => "
                select l3.name region, l2.name oblast, l1.name city, l.relativename as MDU, count(*) tkd, sum(nvl(switches,0)) switches, sum(nvl(n.abons,0)) abons
                from location_o l
                join location_o l1 on l.location2parentlocation = l1.locationid
                join location_o l2 on l1.location2parentlocation = l2.locationid and l2.locationid<>5553
                join location_o l3 on l2.location2parentlocation = l3.locationid
                left join (
                select n.node2location lid, count(*) switches, sum(nvl(s.abons,0)) abons
                from node_o n 
                join (select nodeid,ip_address from SATTAB_OS6250_24M_NODE_O union select nodeid,ip from SATTAB_GT_NODES2_O) st on n.nodeid = st.nodeid
                left join (
                select c.circuit2startnode nid, count(*) abons 
                from service_o s 
                join serviceobject_o so on s.serviceid = so.serviceobject2service
                join circuit_o c on so.serviceobject2object = c.circuitid
                where s.service2servicetype = 1900000009 
                group by c.circuit2startnode) s on n.nodeid = s.nid
                where n.node2nodedef in (1900000076, 1900000115,  1900000102, 1900000103,
                                        1900000105, 1900000106, 1900000119, 1900000267, 1900000308, 1900000336, 1900000379, 1900000398,1900000397,1900000404,1900000407,1900000417,1900000420)
                        and  n.node2functionalstatus =1800000042 
                        and (n.subtype is null or n.subtype <> 'Precision') 
                        and st.ip_address is not null
                        group by n.node2location ) n on l.locationid = n.lid
                                      where l.location2locationtype = 1900000004
                              group by l3.name, l2.name, l1.name, l.relativename
                              having sum(switches)<>0
--                              l3.name <> 'Crimea cluster'
                          order by l3.name, l2.name, l1.name, l.relativename
                                                                      "
            )
        )
    ),

    'CRAMER_4MUSTANG' => array(
        'css' => array('./css/common.css'),
        'template' => 'Для аналізу : <a href=http://msu.kyivstar.ua/Integration/reports/main.php?name=CRAMER_TKD2MDU>
            Звязок ТКД з MDU для виконання розпорядження: Щодо забезпечення реалізації бізнес процесів FTTB в інформаційних системах у філіях</a><br>
            <center><div id=mainTable ksType=table class=commonTable></div></center>',
        'table' => array(
            'mainTable' => array(
                'show_header' => true,
                'db' => 'cramer_admin',
                'sql' => "
            select l3.name as region, l3.locationid regid, l1.locationid cityid, l1.name as city, l.relativename as MDU,
                decode(substr(n.name, instr(n.name, '/')+1),
                'D-Link DES 3200-26', 'D-Link DES-3200-26',
                'D-Link DES3526', 'D-Link DES-3526',
                'D-Link DES3028', 'D-Link DES-3028',
                'D-Link DES3200-28', 'D-Link DES-3200-28',
                'OS6250-24M', 'OS 6250',
                'HuaweiQuidwayS2326', 'Huawei Quidway S2326',
                'HuaweiQuidwayS5300', 'Huawei Quidway S5300',
                substr(n.name, instr(n.name, '/')+1)) as type,
                count(*) as CramerQTY
              from node_o n 
                join (select nodeid,mac_address from SATTAB_OS6250_24M_NODE_O union select nodeid,mac from SATTAB_GT_NODES2_O) st on st.nodeid = n.nodeid and st.mac_address is not null
                join location_o l  on n.node2location = l.locationid
                join locationtype lt on lt.LOCATIONTYPEID = l.location2locationtype and lt.NAME = 'TKD'
                join location_o l1 on l.location2parentlocation = l1.locationid
                join location_o l2 on l1.location2parentlocation = l2.locationid
                join location_o l3 on l2.location2parentlocation = l3.locationid
              where n.node2nodedef in (1900000076, 1900000115,  1900000102, 1900000103,
                                    1900000105, 1900000106, 1900000119, 1900000267, 1900000308, 1900000336, 1900000379, 1900000398,1900000397,1900000404,1900000407,1900000417,1900000420)
                         and (n.subtype is null or n.subtype <> 'Precision')
                         and  n.node2functionalstatus =1800000042
              group by l3.name, l1.name, l3.locationid, l1.locationid, l.relativename,  decode(substr(n.name, instr(n.name, '/')+1),
                'D-Link DES 3200-26', 'D-Link DES-3200-26',
                'D-Link DES3526', 'D-Link DES-3526',
                'D-Link DES3028', 'D-Link DES-3028',
                'D-Link DES3200-28', 'D-Link DES-3200-28',
                'OS6250-24M', 'OS 6250',
                'HuaweiQuidwayS2326', 'Huawei Quidway S2326',
                'HuaweiQuidwayS5300', 'Huawei Quidway S5300',
                substr(n.name, instr(n.name, '/')+1))
              order by  l3.name, l1.name, l.relativename,
                decode(substr(n.name, instr(n.name, '/')+1),
                'D-Link DES 3200-26', 'D-Link DES-3200-26',
                'D-Link DES3526', 'D-Link DES-3526',
                'D-Link DES3028', 'D-Link DES-3028',
                'D-Link DES3200-28', 'D-Link DES-3200-28',
                'OS6250-24M', 'OS 6250',
                'HuaweiQuidwayS2326', 'Huawei Quidway S2326',
                'HuaweiQuidwayS5300', 'Huawei Quidway S5300',
                substr(n.name, instr(n.name, '/')+1))
   ",
                'links' => array(
                    'REGION' => array(
                        'link' => 'main.php?name=CRAMER_4MUSTANG_DET&type=by_reg&regid=<REGID>',
                        'target' => '_blank',
                        'field' => array('REGID')
                    ),
                    'CITY' => array(
                        'link' => 'main.php?name=CRAMER_4MUSTANG_DET&type=by_city&cityid=<CITYID>',
                        'target' => '_blank',
                        'field' => array('CITYID')
                    ),
                    'MDU' => array(
                        'link' => 'main.php?name=CRAMER_4MUSTANG_DET&type=by_mdu&mdu=<MDU>',
                        'target' => '_blank',
                        'field' => array('MDU')
                    )
                ),
                'hiden_fields' => array('CITYID', 'REGID')
            )
        )
    ),

    'CRAMER_4MUSTANG_DET' => array(
        'css' => array('./css/common.css'),
        'template' => 'Для аналізу : <a href=http://msu.kyivstar.ua/Integration/reports/main.php?name=CRAMER_TKD2MDU>
            Звязок ТКД з MDU для виконання розпорядження: Щодо забезпечення реалізації бізнес процесів FTTB в інформаційних системах у філіях</a><br>
            <center><div id=mainTable ksType=table class=commonTable></div></center>',
        'table' => array(
            'mainTable' => array(
                'db' => 'cramer_admin',
                'show_header' => true,
                'sql' => "
        select rownum n, t.*
        from (select l3.name as region, l1.name as city, l.relativename as MDU, substr(n.name, 0, instr(n.name, '/')-1) as hostname, l.name as TKD, l.description as addr,
                   st.ip_address as ip, st.mac_address as mac,
                    decode(substr(n.name, instr(n.name, '/')+1),
                    'D-Link DES 3200-26', 'D-Link DES-3200-26',
                    'D-Link DES3526', 'D-Link DES-3526',
                    'D-Link DES3028', 'D-Link DES-3028',
                    'D-Link DES3200-28', 'D-Link DES-3200-28',
                    'OS6250-24M', 'OS 6250',
                    'HuaweiQuidwayS2326', 'Huawei Quidway S2326',
                        'HuaweiQuidwayS5300', 'Huawei Quidway S5300',
                    substr(n.name, instr(n.name, '/')+1)) as type, st.SERIAL_NUMBER Serial
                  from node_o n 
                    join (select nodeid,mac_address,serial_number,ip_address from SATTAB_OS6250_24M_NODE_O union select nodeid,mac,serial,ip from SATTAB_GT_NODES2_O) st on st.nodeid = n.nodeid and st.mac_address is not null
                    join location_o l  on n.node2location = l.locationid
                    join locationtype lt on lt.LOCATIONTYPEID = l.location2locationtype and lt.NAME = 'TKD'
                    join location_o l1 on l.location2parentlocation = l1.locationid
                    join location_o l2 on l1.location2parentlocation = l2.locationid
                    join location_o l3 on l2.location2parentlocation = l3.locationid
                    --join bee_node cn on cn.node_id = n.objectid and cn.node_state_name = 'В сервисе'
                where n.node2nodedef in (1900000076, 1900000115,  1900000102, 1900000103,
                                    1900000105, 1900000106, 1900000119, 1900000267, 1900000308, 1900000336, 1900000379, 1900000398, 1900000397,1900000404,1900000407,1900000417,1900000420)
                         and (n.subtype is null or n.subtype <> 'Precision')
                         and  n.node2functionalstatus =1800000042
                         and (
                                ('<type>'='by_reg' and l3.locationid ='<regid>') or
                                ('<type>'='by_city' and l1.locationid ='<cityid>') or
                                ('<type>'='by_mdu' and l.relativename ='<mdu>')
                              )
                  order by  l3.name, l1.name, l.relativename,
                    decode(substr(n.name, instr(n.name, '/')+1),
                    'D-Link DES 3200-26', 'D-Link DES-3200-26',
                    'D-Link DES3526', 'D-Link DES-3526',
                    'D-Link DES3028', 'D-Link DES-3028',
                    'D-Link DES3200-28', 'D-Link DES-3200-28',
                    'OS6250-24M', 'OS 6250',
                    'HuaweiQuidwayS2326', 'Huawei Quidway S2326',
                        'HuaweiQuidwayS5300', 'Huawei Quidway S5300',
                substr(n.name, instr(n.name, '/')+1))) t
   "
            )
        )
    ),
    'CRAMER_DISCO_HISTORY' => array(
        'css' => array('./css/common.css'),
        'template' => "<center><font size=4><b>Історія Discover FTTB<br></b></font></center><br>
                        <table id=form border=0 align=center>
                        <tr>
                            <td>Discover з </td><td><div id=pStart ksType=datefield></div></td><td> по </td><td><div id=pEnd ksType=datefield></div></td></td>
                        </tr>
                        <tr>
                            <td>Пошук</td><td><div id=pFlc ksType=combobox></div></td><td></td><td><div id=pFlt ksType=textfield></div></td>
                        </tr>
                        <tr>
                            <td>Фільтр</td><td><div id=pField ksType=combobox></div></td>
                        </tr>
                        <tr>
                            <td align=right colspan=4> <div id=b_exec ksType=button></div> </td>
                        </tr>
                    </table>
            <br>
            <center><div id=mainTable ksType=table table_style=js></div></center>
            ",
        'ext' => "document.loadCfg['autoLoad'] = false;
                 document.ext['pStart']={value : new Date().add(Date.DAY,-30)};
                 document.ext['pEnd']={value : new Date()};
                 document.ext['b_exec']={text: 'Пошук'};
        ",
        'combo' => array(
            'pFlc' => array(
                'db' => 'cramer_admin',
                /*                'sql' => "
                select 'and s.ip_address like nvl(''<pFlt>'',''%'')' id,'IP' name from dual
                union
                select 'and (h.old_value like ''%<pFlt>%'' or h.new_value like ''%<pFlt>%'') and h.FIELD_NAME=''mac_address''' id,'mac' name from dual
                union
                select 'and (h.old_value like ''%<pFlt>%'' or h.new_value like ''%<pFlt>%'') and h.FIELD_NAME=''serial_number''' id,'S/N' name from dual
                union
                select 'and l.name like nvl(''<pFlt>'',''%'')' as id,'Location' as name from dual
                "
*/
                'sql' => "select 1 id,'IP' name from dual
                union
                select 2 id,'mac' name from dual
                union
                select 3 id,'S/N' name from dual
                union
                select 4 id,'Location' as name from dual"
            ),
            'pField' => array(
                'db' => 'cramer_admin',
                'sql' => "
                select 'and h.FIELD_NAME=''mac_address''' id,'MAC' name from dual
                union
                select 'and h.FIELD_NAME=''serial_number''' id,'S/N' name from dual
                union
                select 'and h.FIELD_NAME=''nodetype''' id,'Type' name from dual
                union
                select 'and h.FIELD_NAME=''hostname''' id,'HostName' name from dual
                union
                select '' id,'any' name from dual
                "
            )
        ),
        'table' => array(
            'mainTable' => array(
                'show_header' => true,
                'db' => 'cramer_admin',
                'sql' => "
                   select rownum n, t.* from (
                      select s.ip_address, n.nodeid, regexp_substr(n.name, '[^/]+$') type, l.name location, l.locationid, l.description address,
                             h.idate, h.field_name, h.old_value,m_old.project_code as mustang_location_old, h.new_value, m_new.project_code as mustang_location_new
                             from ks_disc_history h 
                             left join  node_o n  on n.nodeid = h.idobj 
                             left join (select nodeid,ip_address from SATTAB_OS6250_24M_NODE_o union select nodeid,ip ip_address from SATTAB_GT_NODES2_o) s on n.nodeid = s.nodeid
                             left join location_o l on n.node2location = l.locationid
                             left join KS_MUSTANG_SERIAL_NUMBERS m_old on m_old.serial_number=h.old_value and h.field_name='serial_number'
                             left join KS_MUSTANG_SERIAL_NUMBERS m_new on m_new.serial_number=h.new_value and h.field_name='serial_number'
                             where h.disc_name = 'fttb' 
                               and h.idate between to_date('<pStart>', 'dd.mm.yyyy') and to_date('<pEnd>', 'dd.mm.yyyy')
                               and (
                                   ('<pFlc>'=1 and s.ip_address like nvl('<pFlt>','%'))
                                   or ('<pFlc>'=2 and (upper(h.old_value) like upper('%<pFlt>%') or upper(h.new_value) like upper('%<pFlt>%')) and h.FIELD_NAME='mac_address')
                                   or ('<pFlc>'=3 and (h.old_value like '%<pFlt>%' or h.new_value like '%<pFlt>%') and h.FIELD_NAME='serial_number')
                                   or ('<pFlc>'=4 and upper(l.name) like nvl(upper('<pFlt>'),'%'))
                                   )
                               <pField>
                                   and rownum<1000
                                   order by idate) t
				"
            )
        )
    ),
    'CRAMER_LS_GROUP_MPLS' => array(
        'css' => array('./css/common.css'),
        'template' => "<br>
            <center><b>ЛС FTTB привязка до ТКД, DS, SR</b></center>
            <br>
            <center>
            <div id=pList ksType=textarea></div><br>
            <div id=b_exec ksType=button></div><br>
            <div id=mainTable ksType=table table_style=js></div><br>
            </center>
            ",
        'ext' => "document.loadCfg['autoLoad'] = false;
                 document.ext['pList']={width:100, height:200};
                 document.ext['b_exec']={text: 'Пошук'};
        ",
        'table' => array(
            'mainTable' => array(
                'show_header' => true,
                'db' => 'cramer_admin',
                'sql' => "
 		 	       	select s.name as ls,
							 sat.mac_address as mac_switch,
							 ('<a href=" . ($_SERVER['HTTPS'] == "on" ? 'https' : 'http') . "://msu2.kyivstar.ua/Integration/Cramer/Navigator/content/homepage.php?page=homepage&el=node_'||sn.nodeid||' target=_blank>'||sat.ip_address||'</a>') as ip_switch,
							 --sat.ip_address as ip_switch,
							 substr(sn.name, 0, instr(sn.name, '/')-1) as tkd_switch,
                      spp.portnumber tkd_port,
							 pp.name as ds_port,
							 ('<a href=" . ($_SERVER['HTTPS'] == "on" ? 'https' : 'http') . "://msu2.kyivstar.ua/Integration/Cramer/Navigator/content/homepage.php?page=homepage&el=node_'||en.nodeid||' target=_blank>'||substr(en.name, 0, instr(en.name, '/')-1)||'</a>') as ds,
							 --substr(en.name, 0, instr(en.name, '/')-1) as ds,
							 sl.towncity city,
							 (select rtrim(xmlagg(xmlelement(e,t.port_name||' '||substr(t.node_name, 0, instr(t.node_name, '/')-1),',').extract('//text()') order by t.node_name).GetClobVal(),',') sr
											 from (
											 select c.circuitid, c.name cname, sn.nodeid sn, en.nodeid en,
												 c.circuit2startport sp, c.circuit2endport ep, en.node2nodetype nt,
												 en.name node_name, p.name port_name
											 from circuit_o c
											 join node_o sn on sn.nodeid = c.circuit2startnode
											 join node_o en on en.nodeid = c.circuit2endnode
											 join port p on c.circuit2endport = p.portid
											 where c.circuit2circuittype in (1900000015, 1900000016)
												 and ((sn.node2nodetype = 1900000077 and en.node2nodetype = 1900000077) or
													  (sn.node2nodetype = 1900000077 and en.node2nodetype = 1900000079))
											 union all
											 select c.circuitid, c.name cname, en.nodeid sn, sn.nodeid en,
												 c.circuit2endport sp, c.circuit2startport ep, sn.node2nodetype nt,
												 sn.name node_name, p.name port_name
											 from circuit_o c
											 join node_o sn on sn.nodeid = c.circuit2startnode
											 join node_o en on en.nodeid = c.circuit2endnode
											 join port p on c.circuit2startport = p.portid
											 where c.circuit2circuittype in (1900000015, 1900000016)
												 and ((sn.node2nodetype = 1900000077 and en.node2nodetype = 1900000077) or
													  (en.node2nodetype = 1900000077 and sn.node2nodetype = 1900000079))) t
											 where t.nt=1900000079
											 start with t.sn = en.nodeid
												connect by NOCYCLE prior t.en = t.sn and level<15) sr,			 
							 f.rate_pack as BIS_SPEED,
							 ('<a href=" . ($_SERVER['HTTPS'] == "on" ? 'https' : 'http') . "://msu2.kyivstar.ua/Integration/Cramer/Navigator/tools/FTTBAbon/src/fttb_abon.php?action=get_additional_port_info&web&node_id='||sn.objectid||'&ifindex='||p.objectid||'&ctn='||s.name||' target=_blank>'||s.name||'</a>')	as IP_MAC_LS
	   		 	       	from (
							   select trim(regexp_substr(replace('<pList>',chr(13),' '),'[^\n| |\|]+', 1, level)) as ls from dual
							   connect by (regexp_substr(replace('<pList>',chr(13),' '), '[^\n| |\|]+', 1, level)) is not null) a
					  	join SERVICE_o s on s.name = a.ls
						join SERVICEOBJECT_o so on s.SERVICEID = so.SERVICEOBJECT2SERVICE
											  and so.SERVICEOBJECT2DIMOBJECT = 3
						join circuit_o c  on so.SERVICEOBJECT2OBJECT = c.circuitid
										   and c.CIRCUIT2CIRCUITTYPE in (1900000022)
						join node_o sn on c.circuit2startnode = sn.nodeid
                  join port sp on c.circuit2startport = sp.portid
                  join port spp on sp.PARENTPORT2PORT = spp.portid
						join sattab_os6250_24m_node_o sat on sat.nodeid = sn.nodeid
						join node_o en on c.circuit2endnode = en.nodeid
						join port  p on p.portid = c.circuit2endport
						left join port pp on p.parentport2port = pp.portid
						join location_o sl on sn.node2location = sl.locationid
                                                join (select t.account,listagg(t.rate_pack,';') within group (order by t.rate_pack) as rate_pack
                                                        from ks_bis_fttb_subs t
                                                      group by t.account
                                                      ) f on f.account = s.name
						where s.service2servicetype = 1900000009
						order by sl.towncity, substr(en.name, 0, instr(en.name, '/')-1), pp.name,substr(sn.name, 0, instr(sn.name, '/')-1)
                "
            )
        )
    ),
    'FTTB_CABLELEN_BY_LOC' => array(
        'css' => array('./css/common.css'),
        'template' => "<table border=0 align=center>
                           <tr><td><b>Місто: </b></td><td><div id=pCity ksType=combobox></div></td></tr>
                           <tr><td><b>Введіть маску імені площадки: </b></td><td><div id=pLoc ksType=textfield></div></td></tr>
                           <tr><td><div id=b_exec ksType=button></div></td></tr>
                        </table><br>
            <div id=mainTable ksType=table table_style=js></div><br>",
        'ext' => "document.loadCfg['autoLoad'] = false;
                 document.ext['pCity']={width:180};
                 document.ext['pLoc']={width:180};
                 document.ext['b_exec']={text: 'Пошук'};
        ",
        'combo' => array(
            'pCity' => array(
                'db' => 'cramer_admin',
                'sql' => "select locationid id, name from location_o where location2locationtype = 1800000012
                          union select -1, ' All city' from dual order by 2"
            )
        ),
        'table' => array(
            'mainTable' => array(
                'show_header' => true,
                'db' => 'cramer_admin',
                'sql' => "select nvl(ct.name,'Total:') cable_type, sum(cl.sectionlength) section_length
from cableLOCATION cl
join cable c on cl.cblc2cable = c.cableid
join cabletype ct on c.cable2cabletype = ct.CABLETYPEID
where cl.cblc2cable in ( 
     select distinct cl.cblc2cable
from cableLOCATION cl
join sattab_cable sc on cl.cblc2cable = sc.cableid
where cl.cblc2location in ( 
      select l.locationid from location_o l
      join location_o l2 on l.location2parentlocation=l2.locationid
      join location_o l3 on l2.location2parentlocation=l3.locationid
      left join ks_location_ato ato on ato.locationid = l.locationid
      where l.location2locationtype = 1900000004
           and l3.location2parentlocation<>5553 and l3.locationid<>5553
           and ato.locationid is null
            and l.name like nvl('<pLoc>','%')
            and ('<pCity>'='-1' or '<pCity>' = l.location2parentlocation)
        )
      and sc.responsibility = 3)
group by rollup(ct.name)
"
            )
        )
    ),
    'FTTB_CABLELEN_BY_CITY' => array(
        'css' => array('./css/common.css'),
        'template' => "<table border=0 align=center>
                           <tr><td><b>Область: </b></td><td><div id=pReg ksType=combobox></div></td></tr>
                           <tr><td><b>Місто: </b></td><td><div id=pCity ksType=combobox></div></td></tr>
                           <tr><td><b>Введіть маску імені площадки: </b></td><td><div id=pLoc ksType=textfield></div></td></tr>
                           <tr><td><div id=b_exec ksType=button></div></td></tr>
                        </table><br>
            <div id=mainTable ksType=table table_style=js></div><br>",
        'ext' => "document.loadCfg['autoLoad'] = false;
                 document.ext['pReg']={width:180};
                 document.ext['pCity']={width:180};
                 document.ext['pLoc']={width:180};
                 document.ext['b_exec']={text: 'Пошук'};
        ",
        'combo' => array(
            'pCity' => array(
                'db' => 'cramer_admin',
                'sql' => "select locationid id, name from location_o where location2locationtype = 1800000012
                          union select -1, ' All city' from dual order by 2"
            ),
            'pReg' => array(
                'db' => 'cramer_admin',
                'sql' => "select locationid id, fullname as name from location_o where location2locationtype = 1800000011 and fullname is not null
                          union select -1, ' All regions' from dual order by 2"
            )
        ),
        'table' => array(
            'mainTable' => array(
                'show_header' => true,
                'db' => 'cramer_admin',
                'sql' => "select q.reg,nvl(q.city,'Total') as city,nvl(substr(q.mdu,0,12),'Total') as mdu,nvl(q.cable_type,'Total') as cable_type,sum(q.length) as length
from (
select l3.fullname as reg,l2.name as city,l.name as mdu,ct.NAME as cable_type,cl.sectionlength as length
from cableLOCATION cl
join cable c on cl.cblc2cable = c.cableid
join cabletype ct on c.cable2cabletype = ct.CABLETYPEID
join cableLOCATION cl on cl.cblc2cable=c.cableid
join sattab_cable sc on cl.cblc2cable = sc.cableid
join location_o l on l.locationid=cl.cblc2location
join location_o l2 on l2.locationid=l.location2parentlocation
join location_o l3 on l3.locationid=l2.location2parentlocation
where l.location2locationtype = 1900000004
      and l.name like nvl('<pLoc>','%')
      and (nvl('<pCity>','-1')='-1' or '<pCity>' = l.location2parentlocation)
      and (nvl('<pReg>','-1')='-1' or '<pReg>' = l2.location2parentlocation)
      and sc.responsibility = 3
group by l3.fullname,l2.name,l.name,ct.NAME,cl.sectionlength
) q
group by q.reg,rollup(q.city,substr(q.mdu,0,12),q.cable_type)
order by 1,decode(q.city,'Total','ZZZ',q.city),decode(substr(q.mdu,0,12),'Total','ZZZ',substr(q.mdu,0,12)),decode(q.CABLE_TYPE,'Total','ZZZ',q.CABLE_TYPE)"
            )
        )
    ),
    'tkd_not_correct_floor' => array(
        'css' => array('./css/common.css'),
        'template' => "<table border=0 align=center>
            <div id=mainTable ksType=table table_style=js></div><br>",
        'ext' => "document.loadCfg['autoLoad'] = true;
        ",
        'table' => array(
            'mainTable' => array(
                'show_header' => true,
                'db' => 'cramer_admin',
                'sql' => "select distinct vp.mdu_name||'_'||vp.tkd_name as tkd,vp.*,h.floor_count,h.house_id
                from KS_VPORTAL_FTTB_TKD vp
                left join cramer2gis.v_house_entrance@webgis h on h.house_id = vp.house_id and h.remove_date is null
                where to_number(REGEXP_SUBSTR(vp.location_new,'^\d+'))>nvl(h.floor_count,1)
                "
            )
        )
    ),
    'mdu_service_b2b' => array(
        'css' => array('./css/common.css'),
        'template' => "<table border=0 align=center>
            <div id=mainTable ksType=table table_style=js></div><br>",
        'ext' => "document.loadCfg['autoLoad'] = true;
        ",
        'table' => array(
            'mainTable' => array(
                'show_header' => true,
                'db' => 'cramer_admin',
                'sql' => "select regexp_replace(n.name,'\/.*$','')as node,nt.name as type,sat.ip,sat.mac,nvl(pp.name,p.name) as port,regexp_replace(s.name,'^.*\s','') as service
 from circuit_o c
join serviceobject_o so on so.serviceobject2dimobject=3 and so.serviceobject2object=c.circuitid
join service_o s on s.serviceid=so.serviceobject2service and s.service2servicetype not in (1900000009)
join port_vw p on p.portid=c.circuit2startport or p.portid=c.circuit2endport
join port_vw pp on pp.portid=p.parentport2port
join node_o n on n.nodeid=p.port2node and n.name like 'MDU%'
left join nodetype_m nt on nt.nodetypeid=n.node2nodetype
left join (select t1.nodeid,t1.ip,t1.mac from sattab_gt_nodes2_o t1 union select t2.nodeid,t2.ip_address,t2.mac_address from sattab_os6250_24m_node_o t2) sat on sat.nodeid=n.nodeid
"
            )
        )
    ),
    'CRAMER_TKD_BY_ADDRESS' => array(
        'css' => array('./css/common.css'),
        'template' => "<br><center><font size=4><b>Пошук ТКД по місту/адресі<br>
       						</b></font></center><br>
            <table border=0 align=center>
               <tr>
	         <td>Місто: </td><td><div id=pCity ksType=combobox></div></td>
	         <td>Імя ТКД, адреса, HouseID: </td><td><div id=pAdr ksType=textfield></div></td>
	         <td><div id=b_excel ksType=excel></div></td>
	         <td><div id=b_exec ksType=button></div></td>
	       </tr>
             </table>
             <div id=mainTable ksType=table table_style=js></div><br>",
        'ext' => "document.loadCfg['autoLoad'] = false;
                  document.ext['pCity']={width:180};
                  document.ext['pAdr']={width:180};
                  document.ext['b_exec']={text: 'Пошук'};
        ",
        'combo' => array(
            'pCity' => array(
                'db' => 'cramer_admin',
                'sql' => "select locationid id, name from location_o where location2locationtype = 1800000012
                            union select -1, ' All city' from dual
                          order by 2"
            )
        ),
        'table' => array(
            'mainTable' => array(
                'show_header' => true,
                'db' => 'cramer_admin',
                'sql' => "select 
		t.city, t.tkd, t.portal_status, 
       listagg(node,',') within group (order by node) node,
       min(agg)as agg,
       min(agg_atoll) as agg_atoll,
       t.address, t.gpo, t.createddate, t.objectid \"Portal TKD ID\", t.zip houseid
from (
select obl.name as obl, city.name city,
('<'||mdu.name||'>'||'<a href=" . ($_SERVER['HTTPS'] == "on" ? 'https' : 'http') . "://msu2.kyivstar.ua/Integration/Cramer/Navigator/content/homepage.php?page=homepage&el=locd_'||mdu.locationid||' target=_blank>'||mdu.name||'</a>') TKD,
mdu.alias1 as Portal_status,		
('<a href=" . ($_SERVER['HTTPS'] == "on" ? 'https' : 'http') . "://msu2.kyivstar.ua/Integration/Cramer/Navigator/content/homepage.php?el=node_'||n.nodeid||' target=_blank>'||s.ip_address||'</a>') node,
agg.name as agg,
aggst.atoll_site_name as agg_atoll,
mdu.description address,
mdu.alias2 gpo,
mdu.createddate,
mdu.objectid,
mdu.zip,
case when vp.vip_monitor=1 then 'Yes' else 'No' end as vip_monitor
from location_o mdu  
join location_o city on mdu.location2parentlocation = city.locationid
join location_o obl on city.location2parentlocation = obl.locationid
left join node_o n on n.node2location = mdu.locationid
					and n.NODE2NODEDEF in (1900000076, 1900000115,1900000102,1900000103,1900000105,1900000106,1900000119,1900000267,1900000308,1900000336, 1900000379, 1900000397, 1900000398,1900000404,1900000407,1900000417,1900000420,1900000424)
					and (n.subtype<>'Precision' or n.subtype is null)
left join node_o ds on ds.name=n.alias1
left join location_o agg on agg.locationid=ds.node2location
left join sattab_locationsite_o aggst on aggst.locationid=agg.locationid
left join (select nodeid,ip_address from SATTAB_OS6250_24M_NODE_o union select nodeid,ip from SATTAB_GT_NODES2_o) s on s.nodeid = n.nodeid
left join KS_VPORTAL_FTTB_TKD vp on vp.id_tkd=mdu.objectid
where 		mdu.location2locationtype = 1900000004 and
		((city.locationid = '<pCity>' or  '<pCity>' = '-1' or '<pCity>' is null) 
			and (lower(mdu.description) like lower('%<pAdr>%') or lower(mdu.name) like lower('%<pAdr>%') or lower(mdu.zip) like lower('<pAdr>') )) 			
		and ('<pCity>' is not null or '<pAdr>' is not null)
order by city.name, mdu.name, n.name) t
--where rownum<=10000
group by t.city, t.tkd, t.portal_status,  
       t.address, t.gpo, t.createddate, t.objectid, t.zip, t.vip_monitor
"
            )
        )
    ),
    'INTERNET_B2C_FTTB_5' => array(
        'css' => array('./css/common.css'),
        'template' => "<center><font size=4><b>Інформація по WO 'Internet B2C FTTB 5' за період<br></b></font></center><br>
                        <table id=form border=0 align=center>
                        <tr>
                            <td>З </td><td><div id=pStart ksType=datefield></div></td><td> по </td><td><div id=pEnd ksType=datefield></div></td></td>
                        </tr>
                        <tr>
                            <td align=right colspan=4> <div id=b_exec ksType=button></div> </td>
                        </tr>
                    </table>
            <br>
            <center><div id=mainTable ksType=table table_style=js></div></center>
            ",
        'ext' => "document.loadCfg['autoLoad'] = false;
                 document.ext['pStart']={value : new Date().add(Date.DAY,-30)};
                 document.ext['pEnd']={value : new Date()};
                 document.ext['b_exec']={text: 'Пошук'};
        ",
        'table' => array(
            'mainTable' => array(
                'show_header' => true,
                'db' => 'hpsd_stb',
                'sql' => "
select a.TT_WG_NAME,count(a.WOR_ID) as wo_count,sum(a.wo_abon) as wo_abon,sum(a.wo_deadline) as wo_deadline, sum(a.wo_deadline_abon) as wo_deadline_abon from (
SELECT
    wg1.wog_name as TT_WG_NAME,
    wo.WOR_ID,
    case when wo.wor_deadline < wo.wor_actualfinish
    then 1 else 0 end as wo_deadline,
    count(sec.sec_cit_oid) as wo_abon,
    case when wo.wor_deadline < wo.wor_actualfinish
    then count(sec.sec_cit_oid) else 0 end as wo_deadline_abon

  FROM ITSD.SD_WORKORDERS wo
  join ITSD.SD_WOR_CUSTOM_FIELDS wcf on wo.WOR_OID = wcf.WCF_WOR_OID
  left join ITSD.REP_CODES_TEXT rct1 on wo.wor_sta_oid = rct1.rct_rcd_oid
                               and rct1.RCT_LNGPACK_NAME in ('Lpc-default-en_US', 'Lpc-Sdc-en_US')
  left join itsd.cdm_incidents i on wo.WOR_INC_OID = i.INC_OID
  left join ITSD.CDM_WORKGROUPS wg1 on i.INC_ASSIGN_WORKGROUP = wg1.WOG_OID
  left join itsd.cdm_supportedeventcis sec on sec.sec_inc_oid=i.inc_oid
where
     wg1.wog_name like 'FIX-TO-%'
--     and rct1.rct_name not in ('Completed', 'Closed')
     and wcf.WCF_SRV_OID in (189682600235996410,188545243818115836,226900576846830206)
     and cast((from_tz(cast (wo.wor_planstart as TIMESTAMP),'UTC') AT TIME ZONE 'Europe/Kiev')as DATE) between to_date('<pStart>','dd.mm.yyyy') and to_date('<pEnd>','dd.mm.yyyy')
     and i.REG_CREATED  > to_date('<pStart>','dd.mm.yyyy')-365
group by wg1.wog_name, wo.WOR_ID,wo.WOR_DEADLINE, wo.wor_actualfinish
) a
group by TT_WG_NAME				"
            )
        )
    ),

    'FTTB_WO_PPO' => array(
        'css' => array('./css/common.css'),
        'template' => "<center><font size=4><b>Інформація по WO ППО за період<br></b></font></center><br>
                        <table id=form border=0 align=center>
                        <tr>
                            <td>З </td><td><div id=pStart ksType=datefield></div></td><td> по </td><td><div id=pEnd ksType=datefield></div></td></td>
                        </tr>
                        <tr>
                            <td align=right>Сервіс: </td><td align=right colspan=3> <div id=pSrv ksType=combobox></div> </td>
                        </tr>
                        <tr>
                            <td align=right colspan=4> <div id=b_exec ksType=button></div> </td>
                        </tr>
                    </table>
            <br>
            <center><div id=mainTable ksType=table table_style=js></div></center>
            ",
        'ext' => "document.loadCfg['autoLoad'] = false;
                 document.ext['pStart']={value : new Date().add(Date.DAY,-30)};
                 document.ext['pEnd']={value : new Date()};
                 document.ext['pSrv']={width: 300, value : '256227522490960548,285475754583703822,150417167963176935'};
                 document.ext['b_exec']={text: 'Пошук'};
        ",
        'combo' => array(
            'pSrv' => array(
                'db' => 'hpsd_stb',
                'sql' => "select to_char(s.srv_oid) as id,s.srv_name as name from ITSD.CDM_SERVICES s
where s.srv_oid in (256227522490960548,285475754583703822,150417167963176935)
union
select '256227522490960548,285475754583703822,150417167963176935' as id, 'All' as name from dual
order by 2"
            )
        ),
        'table' => array(
            'mainTable' => array(
                'show_header' => true,
                'db' => 'hpsd_stb',
                'sql' => "SELECT
    wo.WOR_ID as wo_id,
    cast((from_tz(cast (wo.wor_planstart as TIMESTAMP),'UTC') AT TIME ZONE 'Europe/Kiev')as DATE) as wo_planstart,
    cast((from_tz(cast (wo.wor_actualstart as TIMESTAMP),'UTC') AT TIME ZONE 'Europe/Kiev')as DATE) as wo_start,
    cast((from_tz(cast (wo.WOR_DEADLINE as TIMESTAMP),'UTC') AT TIME ZONE 'Europe/Kiev')as DATE) as wo_deadline,
    cast((from_tz(cast (wo.wor_actualfinish as TIMESTAMP),'UTC') AT TIME ZONE 'Europe/Kiev')as DATE) as wo_finish,
    to_char(cast((from_tz(cast (wo.wor_actualfinish as TIMESTAMP),'UTC') AT TIME ZONE 'Europe/Kiev')as DATE),'WW') as wo_w,
    wg.wog_name as wo_workgroup,
    s.SRV_NAME as wo_service,
    rct1.rct_name as wo_status,
    WOR_DESCRIPTION as wo_summary,
    wcf.wcf_workordertext10 as wo_solution,
    codl.cdl_name as solution_code,
    lw.loc_name as wo_location,
    lw.loc_description as wo_address,
    decode(wo.wor_attachment_exists,1,'<a target=_blank href=http://hpovsd1:8081/ttwos/ViewAttachFromWO.jsp?vWo='||wo.wor_id||'>Files</a>',null) as link

  FROM ITSD.SD_WORKORDERS wo
  join ITSD.SD_WOR_CUSTOM_FIELDS wcf on wo.WOR_OID = wcf.WCF_WOR_OID
  left join itsd.cdm_locations lw on lw.loc_oid = wcf.WCF_LOC1_OID
  join ITSD.CDM_WORKGROUPS wg on wo.ASS_WORKGROUP = wg.WOG_OID
  join ITSD.CDM_WORKGROUPS_X wgx on wg.WOG_OID = wgx.XWOG_WOG_OID
  join ITSD.CDM_SERVICES s on wcf.WCF_SRV_OID = s.SRV_OID
  left join ITSD.REP_CODES_TEXT rct1 on wo.wor_sta_oid = rct1.rct_rcd_oid and rct1.RCT_LNGPACK_NAME in ('Lpc-default-en_US', 'Lpc-Sdc-en_US')
  left join itsd.sd_codes cod on cod.cod_oid = wo.wor_clo_oid
  left join itsd.sd_codes_locale codl on codl.cdl_cod_oid = cod.cod_oid and codl.cdl_lngpack_name in ('Lpc-default-en_US', 'Lpc-Sdc-en_US')

where
     wcf.WCF_SRV_OID in (<pSrv>)
     and (wo.wor_actualstart between '<pStart>' and '<pEnd>'
      or nvl(wo.wor_actualfinish,sysdate) between '<pStart>' and '<pEnd>')
"
            )
        )
    ),

    'CRAMER_FTTB_RING_SWITCH_STAT' => array(
        'css' => array('./css/common.css'),
        'template' => "<br>
            <center><b>Звіт по кількості обладнання в кільцях доступа</b><p>
            Виводяться кільця з кількістю свічів більше:<div id=pCount ksType=numberfield></div><div id=b_exec ksType=button></div>
            </center>
            <br>
            <center><div id=mainTable ksType=table table_style=js class=commonTable></div></center>
            ",
        'ext' => "document.loadCfg['autoLoad'] = false;
                 document.ext['pCount']={value : 10, minValue : 0, maxValue : 24, allowDecimals : false, width : 40};
                 document.ext['b_exec']={text: 'Пошук'};
        ",
        'table' => array(
            'mainTable' => array(
                'show_header' => true,
                'db' => 'cramer_admin',
                'sql' => "select ring, switch_count, abon_count, c1uster, city, substr(ring,1,12) mdu, l.alias2 gpo,
       n.alias1 ds, p.name ds_port 
from (
select substr(n.name, 0,16) ring, count(distinct n.nodeid) switch_count, count(*) abon_count, l3.name c1uster, l2.name city,
       min(l1.locationid) locationid , max(case when n.name like substr(n.name, 0,16)||'1_1/%' then n.nodeid else -1 end) nodeid
from node_o n
left join circuit_o c on c.circuit2circuittype = 1900000022 and c.circuit2startnode = n.nodeid
--join SATTAB_OS6250_24M_NODE_o s on n.NODEID = s.NODEID
left join location_o l1 on n.node2location = l1.locationid
left join location_o l2 on l1.location2parentlocation = l2.locationid
left join location_o l3 on l2.location2parentlocation = l3.locationid
--where n.NODE2NODEDEF in (1900000102,1900000105,1900000106,1900000103,1900000115, 1900000119, 1900000076, 1900000267, 1900000308)
where n.NODE2NODEDEF in (1900000076, 1900000115, 1900000102, 1900000103, 1900000105, 1900000106, 1900000119, 1900000267, 1900000308, 1900000336, 1900000379, 1900000398, 1900000397,1900000404,1900000407,1900000417,1900000420)
and n.NODE2FUNCTIONALSTATUS = 1800000042
and (n.subtype is null or n.subtype <> 'Precision')
group by substr(n.name, 0,16), l3.name, l2.name
having count(distinct n.nodeid)>(case when '<pCount>' is null then '10'+0 else '<pCount>'+0 end)) t
left join location l on t.locationid = l.locationid
left join node n on t.nodeid = n.nodeid
left join port p on n.alias2 = p.portid
order by  switch_count desc
"
            )
        )
    ),

    'CRAMER_FTTB_STP_ERRORS_old' => array(
        'css' => array('./css/common.css'),
        'template' => "<br>
            <center><b>Discover STP</b><p>
            </center>
            <br>
            <center><div id=mainTable ksType=table table_style=js class=commonTable></div></center>
            ",
        'ext' => "document.loadCfg['autoLoad'] = true;
        ",
        'table' => array(
            'mainTable' => array(
                'show_header' => true,
                'db' => 'cramer_admin',
                'sql' => "select q.ring,
q.stp_ids as stp_ring_ids,
q.tkd_circle,
q.stp as stp_count,
case when q.owner<40960 then '<b>'||q.owner||'</b>' else q.owner end as owner,
case when q.stp=1 then '<b>No stp owner</b>'
     when regexp_count(q.stp_ids,q.owner)>=2 then 'Several owners'
     when regexp_instr(q.stp_ids,q.owner) between 7 and length(q.stp_ids)-5 then '<b>The owner is not first in the ring</b>'
     when q.stp>2 then 'Several values stp'
     when q.owner<40960 then 'The owner is out of range'
end as info
--length(q.stp_ids)/2,regexp_instr(q.stp_ids,q.owner),regexp_count(q.stp_ids,q.owner)
 from (
     select regexp_substr(n.name,'MDU_\w+\d+_\d{3}') ring, 
       listagg(s.stp_priority,', ') WITHIN GROUP (order by n.name) stp_ids,
       listagg(regexp_replace(n.name,'/.*$','')||' ('||s.stp_priority||')',', ') WITHIN GROUP (order by n.name) tkd_circle,
       count(distinct s.stp_priority) stp,
       min(s.stp_priority) owner
from node_o n
join location_o l on n.node2location = l.locationid
join location_o l1 on l1.locationid = l.location2parentlocation
join (select s1.nodeid,s1.stp_priority,s1.erps_ring_id from SATTAB_OS6250_24M_NODE_o s1
union
select s2.nodeid,s2.stp_priority,s2.erps_ring_id from SATTAB_GT_NODES2_O s2) s on n.NODEID = s.NODEID
where n.NODE2NODEDEF in (1900000102,1900000105,1900000106,1900000103,1900000115,1900000119,1900000076,1900000267,1900000308,1900000336,1900000380,1900000379,1900000398,1900000397,1900000404,1900000407)
and n.NODE2FUNCTIONALSTATUS = 1800000042
and (n.subtype is null or n.subtype <> 'Precision')
    and s.stp_priority is not null
    and s.erps_ring_id is null
    and l1.location2parentlocation <> 5553
    and n.name like 'MDU%'
group by regexp_substr(n.name,'MDU_\w+\d+_\d{3}')
) q
where regexp_count(q.stp_ids,q.owner)>=2 or (regexp_instr(q.stp_ids,q.owner) between 7 and length(q.stp_ids)-5) or q.stp>2 or q.owner<40960
order by q.ring
"
            )
        )
    ),

    'CRAMER_FTTB_STP_ERRORS' => array(
        'css' => array('./css/common.css'),
        'template' => "<br>
            <center><b>Discover STP</b><p>
            </center>
            <br>
            <center><div id=mainTable ksType=table table_style=js class=commonTable></div></center>
            ",
        'ext' => "document.loadCfg['autoLoad'] = true;
        ",
        'table' => array(
            'mainTable' => array(
                'show_header' => true,
                'db' => 'cramer_admin',
                'sql' => "select q.ring,
--q.stp_ids as stp_ring_ids,
q.tkd_circle_stp,
q.stp as stp_owner_count,
case when q.owner<40960 then '<b>'||q.owner||'</b>' else q.owner end as owner,
case when q.stp=1 then '<b>No stp owner</b>'
     when regexp_count(q.stp_ids,q.owner)>1 then 'Several owners'
     when regexp_instr(q.stp_ids,q.owner)=1 then '<b>The owner is not first in the ring</b>'
     when q.stp>2 then 'Several values stp'
     when q.owner<40960 then 'The owner is out of range'
end as info
 from (
     select regexp_substr(n.name,'MDU_\w+\d+_\d{3}') ring, 
       listagg(s.stp_priority,', ') WITHIN GROUP (order by regexp_replace(n.name,'/.*$','')) stp_ids,
       listagg(substr(regexp_replace(n.name,'/.*$',''),-6,6)||' ('||s.stp_priority||')',', ') WITHIN GROUP (order by n.name) tkd_circle_stp,
       count(distinct s.stp_priority) stp,
       min(s.stp_priority) owner
from node_o n
join location_o l on n.node2location = l.locationid
join location_o l1 on l1.locationid = l.location2parentlocation
join (select s1.nodeid,s1.stp_priority,s1.erps_ring_id from SATTAB_OS6250_24M_NODE_o s1
union
select s2.nodeid,s2.stp_priority,s2.erps_ring_id from SATTAB_GT_NODES2_O s2) s on n.NODEID = s.NODEID
where n.NODE2NODEDEF in (1900000102,1900000105,1900000106,1900000103,1900000115,1900000119,1900000076,1900000267,1900000308,1900000336,1900000380,1900000379,1900000398,1900000397,1900000404,1900000407,1900000417,1900000420)
and n.NODE2FUNCTIONALSTATUS = 1800000042
and (n.subtype is null or n.subtype <> 'Precision')
    and s.stp_priority is not null
    and s.erps_ring_id is null
    and l1.location2parentlocation <> 5553
    and n.name like 'MDU%'
group by regexp_substr(n.name,'MDU_\w+\d+_\d{3}')
) q
where regexp_count(q.stp_ids,q.owner)>=2 or q.stp>2 or q.owner<40960
and q.ring not in (
select regexp_substr(n.name,'MDU_\w+\d+_\d{3}') as tkd_circle from (
select ns.nodeid,ns.name
 from circuit_o c
join port_vw ps on ps.PORTID=c.circuit2startport
join node_o ns on ns.nodeid=ps.PORT2NODE
join port_vw pe on pe.PORTID=c.circuit2endport
join node_o ne on ne.nodeid=pe.PORT2NODE
where lower(c.name) like 'lldp%' and (ns.name like 'MDU%' and ne.name not like 'MDU%')
union
select ne.nodeid,ne.name
 from circuit_o c
join port_vw ps on ps.PORTID=c.circuit2startport
join node_o ns on ns.nodeid=ps.PORT2NODE
join port_vw pe on pe.PORTID=c.circuit2endport
join node_o ne on ne.nodeid=pe.PORT2NODE
where lower(c.name) like 'lldp%' and (ns.name not like 'MDU%' and ne.name like 'MDU%')
) n
join (select s1.nodeid,s1.stp_priority,s1.erps_ring_id from SATTAB_OS6250_24M_NODE_o s1
union
select s2.nodeid,s2.stp_priority,s2.erps_ring_id from SATTAB_GT_NODES2_O s2) s on n.NODEID = s.NODEID
where s.stp_priority='40960' and s.erps_ring_id is null)
order by q.ring
"
            )
        )
    ),

    'CRAMER_FTTB_ERPS_ERRORS' => array(
        'css' => array('./css/common.css'),
        'template' => "<br>
            <center><b>Discover ERPS</b><p>
            </center>
            <br>
            <center><div id=mainTable ksType=table table_style=js class=commonTable></div></center>
            ",
        'ext' => "document.loadCfg['autoLoad'] = true;
        ",
        'table' => array(
            'mainTable' => array(
                'show_header' => true,
                'db' => 'cramer_admin',
                'sql' => "	   select regexp_substr(n.name,'MDU_[^_]+_\d{3}') ring, 
       listagg(s.erps_ring_id,', ') WITHIN GROUP (order by n.name) erps_ring_ids,
       count(distinct s.erps_ring_id) erps_rings,
       sum(s.erps_owner_port) owner_sum,
       case when count(distinct s.erps_ring_id)>1 then 'Diferent RING Id' else 'No ring owner' end info
from node_o n
join location_o l on n.node2location = l.locationid
join location_o l1 on l1.locationid = l.location2parentlocation
join (select s1.nodeid,s1.erps_ring_id,s1.erps_owner_port from SATTAB_OS6250_24M_NODE_o s1
union
select s2.nodeid,s2.erps_ring_id,s2.erps_owner_port from SATTAB_GT_NODES2_O s2) s on n.NODEID = s.NODEID
where n.NODE2NODEDEF in (1900000102,1900000105,1900000106,1900000103,1900000115,1900000119,1900000076,1900000267,1900000308,1900000336,1900000380,1900000379,1900000398,1900000397,1900000404,1900000407,1900000417,1900000420)
and n.NODE2FUNCTIONALSTATUS = 1800000042
and (n.subtype is null or n.subtype <> 'Precision')
    and not s.erps_ring_id is null
    and l1.location2parentlocation <> 5553
group by regexp_substr(n.name,'MDU_[^_]+_\d{3}')
having not sum(s.erps_owner_port)=1 or
       min(s.erps_ring_id) <> max(s.erps_ring_id)
order by regexp_substr(n.name,'MDU_[^_]+_\d{3}')
"
            )
        )
    ),
    'CRAMER_FTTB_CHANGE_REPLACE' => array(
        'css' => array('./css/common.css'),
        'template' => "<br>
            <center><b>Звіт по заміні комутаторів на мережі FTTB</b>
            </center><p>
                        <table id=form border=0 align=center>
                        <tr>
                            <td>З </td><td><div id=pStart ksType=datefield></div></td><td> по </td><td><div id=pEnd ksType=datefield></div></td></td>
                        </tr>
                        <tr>
                            <td align=right colspan=4> <div id=b_exec ksType=button></div> </td>
                        </tr>
                    </table>

            <br>
            <center><div id=mainTable ksType=table table_style=js class=commonTable></div></center>
            ",
        'ext' => "document.loadCfg['autoLoad'] = false;
                 document.ext['pStart']={value : new Date().add(Date.DAY,-30)};
                 document.ext['pEnd']={value : new Date()};
                 document.ext['b_exec']={text: 'Пошук'};
        ",
        'table' => array(
            'mainTable' => array(
                'show_header' => true,
                'db' => 'cramer_admin',
                'sql' => "select
ch.cha_id as change_id,
rct_status.RCT_NAME as STATUS_CHANGE,
wgch.wog_name as \"FIX-TO-REG\",
pe.per_name as INITIATOR,
lo9.xlos9_bulshorttext4 as tkd_id,
l.loc_name as location,
l.loc_description as address,
wo.wor_id as wo_id,
rct2.rct_name as wo_state,
cast((from_tz(cast (wo.wor_planstart as TIMESTAMP),'UTC') AT TIME ZONE 'Europe/Kiev')as DATE) as WO_start,
cast((from_tz(cast (wo.wor_deadline as TIMESTAMP),'UTC') AT TIME ZONE 'Europe/Kiev')as DATE) as WO_deadline,
cast((from_tz(cast (wo.wor_actualfinish as TIMESTAMP),'UTC') AT TIME ZONE 'Europe/Kiev')as DATE) as WO_end,
round((wo.wor_deadline-wo.wor_actualfinish)*24) as wo_dur_hours,
wgwo.wog_name as gpo_name,
--s.srv_name as wo_service,
--wcf.wcf_workordertext10 as solition, --нужно сделать как в письме, сделать две колонки до символов \/ и после символов \/
vp.mdu_lot,
regexp_replace(wcf.wcf_workordertext10,'\/.*$|\\\.*$','') as WO_CNT_SWITCH,
cr.cnt as CRAMER_CNT_SWITCH,
regexp_replace(wcf.wcf_workordertext10,'^.*\/|^.*\\\','') as QUANTITY_CLIENTS,
cr.serv as CRAMER_CNT_CLIENTS,
cr.ip as NODE_IP,
cr.type as NODE_TYPE
from itsd.sd_changes@ttwos ch
left join itsd.sd_workorders@ttwos wo on wo.wor_cha_oid=ch.cha_oid
left join itsd.sd_wor_custom_fields@ttwos wcf on wcf.wcf_wor_oid=wo.wor_oid
join ITSD.REP_CODES_TEXT@TTWOS rct2 on wo.wor_sta_oid = rct2.rct_rcd_oid and rct2.RCT_LNGPACK_NAME in ('Lpc-default-en_US', 'Lpc-Sdc-en_US')
left join itsd.cdm_workgroups@ttwos wgwo on wgwo.wog_oid=wo.ass_workgroup
left join itsd.cdm_workgroups@ttwos wgch on wgch.wog_oid=ch.ass_wog_oid
left join itsd.CDM_SERVICES@ttwos s on s.srv_oid=wcf.wcf_srv_oid
left join itsd.cdm_locations@ttwos l on l.loc_oid=wcf.wcf_loc1_oid
left join itsd.cdm_locations_x_sd_9@TTWOS lo9 on lo9.xlos9_loc_oid=l.loc_oid
left join itsd.cdm_persons@ttwos pe on pe.per_oid=ch.cha_per_oid
left join itsd.REP_CODES@TTWOS rc_status on ch.CHA_STA_OID = rc_status.RCD_OID
left join itsd.REP_CODES_TEXT@TTWOS rct_status on rc_status.RCD_OID = rct_status.RCT_RCD_OID and rct_status.RCT_LNGPACK_NAME in ('Lpc-default-en_US', 'Lpc-Sdc-en_US') --and rct_status.RCT_LNGPACK_OID = 5066554156580866
left join ks_vportal_fttb_tkd vp on to_char(vp.id_tkd)=lo9.xlos9_bulshorttext4
left join (select l.objectid, count(n.nodeid) as cnt,
 listagg(s.ip,',') WITHIN GROUP (order by s.nodeid) as ip,
 listagg(regexp_replace(n.name,'^.*\/'),',') WITHIN GROUP (order by n.nodeid) as type,
 sum(cir.serv) as serv
 from ks_disc_history his
join node_o n on n.nodeid=to_number(his.idobj)
join location_o l on l.locationid=n.node2location
left join (select s1.nodeid,s1.ip from sattab_gt_nodes2_o s1
union
select s2.nodeid,s2.ip_address as ip from sattab_os6250_24m_node_o s2
) s on s.nodeid=n.nodeid
--left join nodetype_m nt on nt.nodetypeid=n.node2nodetype
left join (select t.circuit2startnode as nodeid,count(t.circuitid) as serv from circuit_o t
where t.circuit2circuittype=1900000022 --and t.subtype='FTTB'
group by t.circuit2startnode
union
select t.circuit2endnode as nodeid,count(t.circuitid) as serv from circuit_o t
where t.circuit2circuittype=1900000022 --and t.subtype='FTTB'
group by t.circuit2endnode
) cir on cir.nodeid=n.nodeid
where lower(his.field_name)='mac_address' and lower(his.object)='node'
   and his.old_value is not null and his.new_value is not null and to_number(his.idobj) is not null
   and his.idate between to_date('<pStart>','dd.mm.YYYY') and to_date('<pEnd>','dd.mm.YYYY')
group by l.objectid) cr on cr.objectid=lo9.xlos9_bulshorttext4
where 
s.srv_id=3494 --Заміна комутаторів на мережі FTTB
and wo.reg_created >= to_date('23.11.2020 00:00:00','dd.mm.yyyy hh24:mi:ss')
and wo.reg_created >= to_date('<pStart>','dd.mm.YYYY') and nvl(wo.wor_actualfinish,to_date('<pEnd>','dd.mm.YYYY'))<=to_date('<pEnd>','dd.mm.YYYY')
order by wo.wor_actualfinish"
            )
        )
    ),
    'CRAMER_IP_DISCO' => array(
        'css' => array('./css/common.css'),
        'template' => "<br><center><font size=4><b>Список IP адрес FTTB, які не внесені в систему Cramer<br></b></font></center><br>
                            Список виключень:
                            <li> x.x.x.252
                            <li> x.x.x.253
                            <li> x.x.x.254
                            <li> 10.200.255.x
                            <li> 10.200.234.x
                            <br><br>
<div id=mainTable ksType=table table_style=js class=commonTable></div></center>
            ",
        'ext' => "document.loadCfg['autoLoad'] = true;
        ",
        'table' => array(
            'mainTable' => array(
                'show_header' => true,
                'db' => 'cramer_admin',
                'sql' => "select t.ip, t.sys_name,
decode(t.sys_oid,
'.1.3.6.1.4.1.2011.2.23.92','Huawei Quidway S2326',
'.1.3.6.1.4.1.2011.2.23.530','Huawei S2320',
'.1.3.6.1.4.1.2011.2.23.88','Huawei S2309',
'.1.3.6.1.4.1.2011.2.23.175','Huawei Quidway S5300',
'.1.3.6.1.4.1.2011.2.23.409','Huawei Quidway S5320',
'.1.3.6.1.4.1.171.10.113.1.5','D-Link DES 3200-26',
'.1.3.6.1.4.1.171.10.113.1.3','D-Link DES3200-28',
'.1.3.6.1.4.1.171.10.63.6','D-Link DES3028',
'.1.3.6.1.4.1.171.10.76.28.1','D-Link DES1210',
'.1.3.6.1.4.1.6486.800.1.1.2.1.11.1.2','OS6250-24M',
'.1.3.6.1.4.1.6486.800.1.1.2.2.4.1.1','Alcatel 6224',
'.1.3.6.1.4.1.890.1.5.8.68','ZyXEL MES3500-24',
'.1.3.6.1.4.1.2011.2.23.94','Huawei S2352',
'.1.3.6.1.4.1.2011.2.23.223','Huawei S2350',
'.1.3.6.1.4.1.171.10.61.3','DLINK DES-2108',
'.1.3.6.1.4.1.3807.1.482821','Fengine S4830-28T-X',
'.1.3.6.1.4.1.3807.1.482603','Fengine S4820-26T-X',
'.1.3.6.1.4.1.3807.1.482812','Fengine S4820-28T-X',
'.1.3.6.1.4.1.3807.1.482816','Fengine S4820-28T-X',
'.1.3.6.1.4.1.3807.1.482823','Fengine S4830-28T-X',
'.1.3.6.1.4.1.3807.1.282001','Fengine S4820-28T-TF',
'.1.3.6.1.4.1.8886.6.307','ISCOM2624G-4C',
t.sys_oid) as sysoid,
 t.updated_at as \"DATE\"
 from KS_FTTB_DISCO_NETWORK t
where t.updated_at is not null
--order by 4 desc",
                'links' => array(
                    'IP' => array(
                        'link' => 'https://webgui.netcool01.kyivstar.ua:16311/ibm/console/webtop/cgi-bin/newcgitelnet.cgi?NodeAlias=<IP>',
                        'target' => '_blank',
                        'field' => array('IP')
                    )
                )
            )
        )
    ),
    'CRAMER_FTTB_CHANGE_DEL' => array(
        'css' => array('./css/common.css'),
        'template' => "<br>
            <center><b>Звіт по демонтажу комутаторів на мережі FTTB</b>
            </center><p>
                        <table id=form border=0 align=center>
                        <tr>
                            <td>З </td><td><div id=pStart ksType=datefield></div></td><td> по </td><td><div id=pEnd ksType=datefield></div></td></td>
                        </tr>
                        <tr>
                            <td align=right colspan=4> <div id=b_exec ksType=button></div> </td>
                        </tr>
                    </table>

            <br>
            <center><div id=mainTable ksType=table table_style=js class=commonTable></div></center>
            ",
        'ext' => "document.loadCfg['autoLoad'] = false;
                 document.ext['pStart']={value : new Date().add(Date.DAY,-30)};
                 document.ext['pEnd']={value : new Date()};
                 document.ext['b_exec']={text: 'Пошук'};
        ",
        'table' => array(
            'mainTable' => array(
                'show_header' => true,
                'db' => 'cramer_admin',
                'sql' => "SELECT rownum n,
     CHA_ID as CHANGE_ID,
     rct_status.RCT_NAME as status_change,
     codl.cdl_name as solution_code_change,
     wg1.wog_name as \"FIX-TO-REG\",
     lo9.xlos9_bulshorttext4 as tkd_id,
     lo.LOC_NAME as location,
     lo.loc_description as address,
     p.PER_NAME as initiator,
     c.CHA_DESCRIPTION as  summary,
     cast((from_tz(cast (c.REG_CREATED as TIMESTAMP),'UTC') AT TIME ZONE 'Europe/Kiev')as DATE) as change_created,
     cast((from_tz(cast (c.REG_CREATED as TIMESTAMP),'UTC') AT TIME ZONE 'Europe/Kiev')as DATE)+3 as change_deadline,
     cast((from_tz(cast (c.cha_actualfinish as TIMESTAMP),'UTC') AT TIME ZONE 'Europe/Kiev')as DATE) as change_to_complete,
     --cast((from_tz(cast (c.cha_deadline as TIMESTAMP),'UTC') AT TIME ZONE 'Europe/Kiev')as DATE) as change_deadline, -- remedy wo end

     wo2.wor_id as wo2_id, rct2.rct_name as wo2_state, codl2.cdl_name as solution_code2,
     cast((from_tz(cast (wo2.wor_latestart as TIMESTAMP),'UTC') AT TIME ZONE 'Europe/Kiev')as DATE) as wo2_start,
     cast((from_tz(cast (wo2.wor_deadline as TIMESTAMP),'UTC') AT TIME ZONE 'Europe/Kiev')as DATE) as wo2_deadline,
     cast((from_tz(cast (wo2.wor_actualfinish as TIMESTAMP),'UTC') AT TIME ZONE 'Europe/Kiev')as DATE) as wo2_end,
     round((wo2.wor_actualfinish-wo2.wor_deadline)*24) as dur_hours2,
     wg2.wog_name as GPO_NAME,
     vp.mdu_lot  


FROM ITSD.SD_CHANGES@TTWOS c
join ITSD.sd_cha_custom_fields@TTWOS chf on chf.ccu_cha_oid = c.cha_oid
left join ITSD.CDM_WORKGROUPS@TTWOS wg1 on c.ASS_WOG_OID = wg1.WOG_OID
left join ITSD.cdm_locations@TTWOS lo on lo.loc_oid=chf.ccu_loc1_oid
left join itsd.cdm_locations_x_sd_9@TTWOS lo9 on lo9.xlos9_loc_oid=lo.loc_oid
left join itsd.REP_CODES@TTWOS rc_status on c.CHA_STA_OID = rc_status.RCD_OID
left join itsd.REP_CODES_TEXT@TTWOS rct_status on rc_status.RCD_OID = rct_status.RCT_RCD_OID and rct_status.RCT_LNGPACK_NAME in ('Lpc-default-en_US', 'Lpc-Sdc-en_US')
left join itsd.CDM_PERSONS@TTWOS p on c.CHA_PER_OID = p.PER_OID

left join itsd.sd_codes@TTWOS cod on cod.cod_oid = c.cha_closurecode
left join itsd.sd_codes_locale@TTWOS codl on codl.cdl_cod_oid = cod.cod_oid and codl.cdl_lngpack_name in ('Lpc-default-en_US', 'Lpc-Sdc-en_US')

join itsd.SD_WORKORDERS@TTWOS wo2 on c.CHA_OID = wo2.WOR_CHA_OID
join ITSD.REP_CODES_TEXT@TTWOS rct2 on wo2.wor_sta_oid = rct2.rct_rcd_oid and rct2.RCT_LNGPACK_NAME in ('Lpc-default-en_US', 'Lpc-Sdc-en_US')
join ITSD.CDM_WORKGROUPS@TTWOS wg2 on wo2.ASS_WORKGROUP = wg2.WOG_OID -- and wg2.WOG_NAME in ('Provide_Service','MS-zvyazok','NetStroy_Group','TK-Zvyazok','Universal_Vega')
join ITSD.CDM_WORKGROUPS_X@TTWOS wgx2 on wg2.WOG_OID = wgx2.XWOG_WOG_OID and (wgx2.XWOG_WOGBOOLEAN1=1 or wgx2.xwog_wogboolean7=1)
left join itsd.sd_codes@TTWOS cod2 on cod2.cod_oid = wo2.wor_clo_oid
left join itsd.sd_codes_locale@TTWOS codl2 on codl2.cdl_cod_oid = cod2.cod_oid and codl2.cdl_lngpack_name in ('Lpc-default-en_US', 'Lpc-Sdc-en_US')
left join ks_vportal_fttb_tkd vp on vp.id_tkd=lo9.xlos9_bulshorttext4

where
c.cha_tem_oid=274252111353763284  --темплей Internet B2C FTTB 8

and c.REG_CREATED > to_date('08.12.2020','dd.mm.YYYY') 
and wo2.reg_created >= to_date('<pStart>','dd.mm.YYYY') and nvl(wo2.wor_actualfinish,to_date('<pEnd>','dd.mm.YYYY'))<=to_date('<pEnd>','dd.mm.YYYY')
order by wo2.wor_actualfinish"
            )
        )
    ),

    'CRL_AP_FTTB_GROUPS' => array(
        'css' => array('./css/common.css'),
        'template' => "<br><center><font size=4><b>Групи відповідальних за обслуговування міст по FTTB</b></font></center><br>
<div id=mainTable ksType=table table_style=js class=commonTable></div></center>
            ",
        'ext' => "document.loadCfg['autoLoad'] = true;
        ",
        'table' => array(
            'mainTable' => array(
                'show_header' => true,
                'db' => 'cramer_admin',
                'sql' => "select l2.province as region,l.name as city,l.province,l.alias2 as workgroup, t.t1 as users
 from location_o l
left join location_o l2 on l2.locationid=l.location2parentlocation
left join (select '<table class=commonTable>'||listagg('<tr><td width=100>'||first_name||'</td><td width=100>'||last_name||'</td><td width=250>'||email||'</td><td width=100>'||mobile||'</td></tr>','') within group (order by 1)||'</table>' as t1, GROUP_NAME
  from ttwos.ap_ttwos_pers_group@ap3
  where GROUP_NAME like 'FIX-TO%'
  group by GROUP_NAME) t on t.GROUP_NAME = l.alias2
where l.location2locationtype=1800000012
order by 1,2",
                'width' => array('REGION' => 5, 'CITY' => 20, 'PROVINCE' => 20, 'WORKGROUP' => 10),
            )
        )
    ),

    'CRAMER_MDU_DS' => array(
        'css' => array('./css/common.css'),
        'template' => "<br><center><font size=4><b>MDU AGG</b></font></center><br>
<div id=mainTable ksType=table table_style=js class=commonTable></div></center>
            ",
        'ext' => "document.loadCfg['autoLoad'] = true;
        ",
        'table' => array(
            'mainTable' => array(
                'show_header' => true,
                'db' => 'cramer_admin',
                'sql' => "select t.city, t.mdu, listagg(t.ds,';') within group (order by 1) as ds,t.agg,t.atoll_site_name from (
select distinct s2.name as city, substr(n.name,0,12) as mdu,ds.name as ds,l.name as agg,st.atoll_site_name
from node_o n
left join location_o s on s.locationid=n.node2location
left join location_o s2 on s2.locationid=s.location2parentlocation
left join node_o ds on ds.name like n.alias1
left join location_o l on l.locationid=ds.node2location
left join sattab_locationsite_o st on st.locationid=l.locationid
where (n.name like 'MDU%' or n.name like 'PON%') and n.alias1 is not null and n.alias1<>'DUMMY'
) t
group by t.city,t.mdu,t.agg,t.atoll_site_name",
                'width' => array('CITY' => 20, 'MDU' => 10, 'DS' => 100, 'AGG' => 50),
            )
        )
    ),
    'CRAMER_DISABLED_FTTB_CITY' => array(
        'css' => array('./css/common.css'),
        'template' => "<br><center><font size=4><b>Стан мережі FTTB в розрізі міст</b></font></center><br>
<div id=mainTable ksType=table table_style=js class=commonTable></div></center>
            ",
        'ext' => "document.loadCfg['autoLoad'] = true;
        ",
        'table' => array(
            'mainTable' => array(
                'show_header' => true,
                'db' => 'cramer_admin',
                'sql' => "select q.obl,q.city,
q.all_tkd,nvl(w.cnt_tkd,0) as not_working_tkd,case when q.all_tkd<>0 then to_char(round((nvl(w.cnt_tkd,0)/q.all_tkd)*100,2),'990D99') else '100' end ||'%' as nwt,
q.all_house,nvl(w.cnt_house,0) as not_working_house,case when q.all_house<>0 then to_char(round((nvl(w.cnt_house,0)/q.all_house)*100,2),'990D99') else '100' end ||'%' as nwh,
q.all_node,nvl(w.cnt_node,0) as not_working_node,case when q.all_node<>0 then to_char(round((nvl(w.cnt_node,0)/q.all_node)*100,2),'990D99') else '100' end ||'%' as nwn,
q.all_service,nvl(w.cnt_service,0) as not_working_service ,case when q.all_service<>0 then to_char(round((nvl(w.cnt_service,0)/q.all_service)*100,2),'990D99') else '100' end ||'%' as nws
 from (select nvl(l3.name,'Total') as obl, nvl(l2.name,'-----') as city,
count(distinct l.locationid) all_tkd, count(distinct l.zip) all_house, count(distinct n.nodeid) as all_node, count(distinct cir.circuitid) as all_service
 from node_o n
left join circuit_o cir on cir.circuit2startnode=n.nodeid and cir.circuit2circuittype=1900000022 and cir.subtype='FTTB'
join location_o l on l.locationid=n.node2location
join location_o l2 on l2.locationid=l.location2parentlocation
join location_o l3 on l3.locationid=l2.location2parentlocation
where l3.locationid<>5553 and l3.location2locationtype=1800000011 and n.name not like '%ODF%'
and (n.name like 'MDU%' or n.name like 'PON%') and nvl(n.node2functionalstatus,1800000042)=1800000042
group by rollup(l3.name, l2.name)
) q
left join (select 
nvl(l3.name,'Total') as obl, nvl(l2.name,'-----') as city,
count(distinct l.locationid) cnt_tkd, count(distinct l.zip) cnt_house, count(distinct n.nodeid) as cnt_node, count(distinct cir.circuitid) as cnt_service
from msu_alarm@cic2 t
join (select nodeid,ip from sattab_gt_nodes2_o union select nodeid,ip_address from sattab_os6250_24m_node_o) s on s.ip=t.nodeip
join node_o n on n.nodeid=s.nodeid
left join circuit_o cir on cir.circuit2startnode=n.nodeid and cir.circuit2circuittype=1900000022 and cir.subtype='FTTB'
join location_o l on l.locationid=n.node2location
join location_o l2 on l2.locationid=l.location2parentlocation
join location_o l3 on l3.locationid=l2.location2parentlocation
where t.alarmclass=100 and t.dateclose is null
group by rollup(l3.name,l2.name)
) w on w.obl=q.obl and w.city=q.city
order by decode(q.obl,'Total','ЯЯЯ',q.obl),decode(q.city,'-----','ЯЯЯ',q.city)",
                #                'width' => array ('CITY'=>20,'MDU'=>10,'DS'=>100,'AGG'=>50),
            )
        )
    ),

    'CRAMER_FTTB_MONITORING' => array(
        'css' => array('./css/common.css'),
        'template' => "<br><center><font size=4><b>Список аварій в моніторингу FTTB<br></b></font></center><br>
                           <table border=0 align=center>
                            <tr>
                             <td>Агрегація:</td>
                             <td> <div id=pAG ksType=combobox> </td>
                            </tr>
                            <tr>
                             <td>Робоча група:</td>
                             <td> <div id=pWG ksType=combobox> </td>
                            </tr>
                            <tr>  
                             <td><div id=b_exec ksType=button> </td>
                            </tr>
                           </table><br>
            <div id=tFTTB ksType=table class=commonTable table_style=js></div>
            ",
        'ext' => "document.loadCfg['autoLoad'] = false;
                  document.loadCfg['autoRefresh'] = 300;
        ",
        'combo' => array(
            'pAG' => array(
                'db' => 'cramer',
                'sql' => "select '' as id, 'Actual' as name from dual
union
select ' or 1=1' as id, 'All' as name from dual
"
            ),
            'pWG' => array(
                'db' => 'cramer_admin',
                'sql' => "select '%' as id, 'All' as name from dual
union
select l.alias2 as id, l.alias2 as name from location_o l
where l.location2locationtype=1800000012 and l.alias2 is not null
group by l.alias2
"
            )
        ),
        'table' => array(
            'tFTTB' => array(
                'show_header' => true,
                'db' => 'cramer_admin',
                'sql' => "select rownum as n,nc.id,
'<a target=_blank href=http://10.44.2.229:8081/ServicePages/HDViewEditIncident.jsp?vInc='||nc.tt||'>'||nc.tt||'</a>' as tt,
nc.nodeip,nc.description, 
                 to_char(nc.datecreate,'yyyy-mm-dd hh24:mi:ss') as datecreate,
                 to_char(nc.datestart,'yyyy-mm-dd hh24:mi:ss') as datestart,to_char(nc.datefinish,'yyyy-mm-dd hh24:mi:ss') as datefinish,
                 to_char(nc.dateclose,'yyyy-mm-dd hh24:mi:ss') as dateclose,
                 round((cast(nvl(nc.dateclose,sysdate) as date) - cast(nc.datecreate as date))*24,2) as dur_h,
'<a target=_blank href=" . ($_SERVER['HTTPS'] == "on" ? 'https' : 'http') . "://msu2.kyivstar.ua/Integration/Cramer/Navigator/content/homepage.php?page=homepage&el=node_'||n.nodeid||'>'||n.name||'</a>' as nodeName,
--                 n.name as nodeName,
l.name as location,l.address,pl.province as city,
                 nvl(pl.alias2,'FIX-TO-CO') as WG,nvl(l.alias2,'FIX-TO-CO') as GPO,
                 case when r.serial is not null then '<font color=red>'||n.alias1||'<font>' else n.alias1 end as ds,
                 r.tt as ds_tt,
                 nc.countwait
                    from msu_alarm@cic2 nc
                 join (select s1.nodeid,s1.ip from sattab_gt_nodes2_o s1
                       union
                       select s2.nodeid,s2.ip_address from sattab_os6250_24m_node_o s2) st on st.ip=nc.nodeip
                 join node_o n on n.nodeid=st.nodeid
                 join location_o l on l.locationid=n.node2location
                 join location_o pl on pl.locationid=l.location2parentlocation
                 left join (select max(serverserial) as serial, min(ttid) as tt, node 
                        from ks_full_status4gis
                        where class = 50000 and summary = 'HostisDOWN'
--                          and substr (node, 1,2) in ('ds', 'tr', 'sr')
                        group by node
                 ) r on n.alias1 like r.node||'/%'
                where nc.dateclose is null 
                      and ((nc.countwait>0 and nc.alarmclass=100) or ((nc.datecreate<(sysdate-1/3) or nc.countwait>19) and nc.alarmclass=80600) <pAG>)
                      and nvl(pl.alias2,'FIX-TO-CO') like nvl('<pWG>','%')
"
            )

        )
    ),

    'CRAMER_FTTB_DS_MONITORING' => array(
        'css' => array('./css/common.css'),
        'template' => "<br><center><font size=4><b>Список активних аварій по FTTB DS<br></b></font></center><br>
                           <table border=0 align=center>
                           </table><br>
            <div id=tFTTB ksType=table class=commonTable table_style=js></div>
            ",
        'ext' => "document.loadCfg['autoLoad'] = true;
                  document.loadCfg['autoRefresh'] = 300;
        ",
        'table' => array(
            'tFTTB' => array(
                'show_header' => true,
                'db' => 'cramer_admin',
                'sql' => "select * from v_fttb_ds_alarm t",
                'width' => array('FIRSTOCCURRENCE' => 150, 'MDU' => 500),
            )

        )
    ),

    'CRAMER_FTTB_TEST_DEMONTAGE' => array(
        'css' => array('./css/common.css'),
        'template' => "<br><center><font size=4><b>Оцінка можливості демонтувати комутатор з ТКД<br></b></font></center><br>
                           <table border=0 align=center>
                            <tr>
                             <td>Після демонтажу має залишитись вільних портів:</td>
                             <td> <div id=pN ksType=textfield> </td>
                            </tr>
                            <tr>  
                             <td><div id=b_exec ksType=button> </td>
                            </tr>
                           </table><br>
            <div id=tFTTB ksType=table class=commonTable table_style=js></div>
            ",
        'ext' => "document.loadCfg['autoLoad'] = false;
                  document.ext['pN']={value: 1, width: 40 };
        ",
        'table' => array(
            'tFTTB' => array(
                'show_header' => true,
                'db' => 'cramer_admin',
                'sql' => "   select l3.name oblast, l2.name city, l.name tkd, p.all_ports, p.broken_ports, nvl(c.all_clients,0) all_clients,
        (p.all_ports - p.broken_ports - nvl(c.all_clients,0) ) current_ports,
       (p.all_ports - p.broken_ports - nvl(c.all_clients,0) - 24) demontage_result
from (
select n.node2location locid, count(*) all_ports, sum(decode(p.port2provisionstatus,1900000085,1,0)) broken_ports
from location_o l
join node_o n on l.locationid = n.node2location
join port_vw p on n.nodeid = p.port2node
where
    n.name like 'MDU%' and n.name not like '%ODF%'
--    n.node2nodedef in (1900000076, 1900000115,  1900000102, 1900000103, 1900000105, 1900000106, 1900000119, 1900000267, 1900000308)
    and n.node2functionalstatus = 1800000042 -- in service
    and p.port2porttype = 1900000025
    and l.locationid <> 28921 --deleted
    and l.name like 'MDU%'
group by n.node2location
having count(distinct n.nodeid)>1) p
left join (select n.node2location locid, count(*) all_clients
from location_o l
join node_o n on l.locationid = n.node2location
join circuit_o c on n.nodeid = c.circuit2startnode or n.nodeid = c.circuit2endnode
join serviceobject_o so on c.circuitid = so.serviceobject2object
where
    n.name like 'MDU%' and n.name not like '%ODF%'
--    n.node2nodedef in (1900000076, 1900000115,  1900000102, 1900000103, 1900000105, 1900000106, 1900000119, 1900000267, 1900000308)
    and n.node2functionalstatus = 1800000042 -- in service
    and l.locationid <> 28921 --deleted
group by n.node2location
) c on p.locid = c.locid 
join location_o l on p.locid = l.locationid
join location_o l2 on l.location2parentlocation = l2.locationid
join location_o l3 on l2.location2parentlocation = l3.locationid
where (p.all_ports - p.broken_ports - nvl(c.all_clients,0) - 24) > (nvl('<pN>','0')+0) and not '<pN>' is null
",
                //                'width' => array ('MDU'=>500),
            )

        )
    ),

    'CRAMER_MAINS_AGG' => array(
        'css' => array('./css/common.css'),
        'template' => "
	<br><center><font size=4><b>Звіт по часу автономної роботи AGG<br></b></font></center><br>
                <table id=form border=0 align=center>
                        <tr>
                            <td><h2>Період з:</h></td><td> <div id=pStart ksType=datefield></div> </td>
                        </tr>
                        <tr>
                            <td><h2>Період по:</h></td><td> <div id=pEnd ksType=datefield></div> </td>
                        </tr>
                        <tr>
                            <td align=right colspan=2><div id=b_excel ksType=excel></div><div id=b_exec ksType=button></div> </td>
                        </tr>
                </table>
            <br>
            <center><div id=mainTable ksType=table table_style=js></div></center>
            ",
        'ext' => "document.loadCfg['autoLoad'] = false;
                  document.ext['pStart']={width:200,value : new Date().add(Date.MONTH,-1)};
                  document.ext['pEnd']={width:200,value : new Date()};
        ",
        'table' => array(
            'mainTable' => array(
                'db' => 'reporter',
                'sql' => "select
r.region,
r.node, substr(t.nodealias,0,6) as nodealias,
 t.firstoccurrence as mains_time,
 r.firstoccurrence as HostIsDown, r.cleartime,
count (*) as cnt,
round(AVG (round((r.firstoccurrence - t.firstoccurrence)*(24*60))),2) as AVG_MAINS_TO_OML,
(SUM (round(((case when r.cleartime<'01.01.1971' and r.deletedat is null and r.severity>0 then sysdate
      when r.cleartime<'01.01.1971' and r.deletedat is not null then r.deletedat
      else r.cleartime end) - r.firstoccurrence)*(24*60)))) as HostIsDown_MIN
from reporter_status t
join reporter_status r on  t.location = r.location
and r.firstoccurrence between to_date('<pStart>','dd.mm.yyyy') and to_date('<pEnd>','dd.mm.yyyy')
and r.class in ( 50000 )
and r.region<> 'Default'
and r.CLEARTIME >= to_date('<pStart>','dd.mm.yyyy')
and r.summary in ('Host is DOWN')
where
substr(t.nodealias,0,6) in (SELECT 
  nvl(l.atoll_name,l.pm_name) AS loc
  FROM mv_cramer_location l
JOIN mv_cramer_node n ON n.locationid=l.locationid and n.type like 'Alcatel%'
JOIN CRAMER.port_v@CRAMER_PROD pv on n.nodeid = pv.PORT2NODE and (pv.description LIKE 'MDU%' or pv.description LIKE 'PON%')
group by nvl(l.atoll_name,l.pm_name)
)
and upper(t.summary) like '%MAINS%'
and (round((r.firstoccurrence - t.firstoccurrence)*(24*60)) > 0
and round((r.firstoccurrence - t.firstoccurrence)*(24*60)) < 720)
and t.firstoccurrence between to_date('<pStart>','dd.mm.yyyy') and to_date('<pEnd>','dd.mm.yyyy')
and r.firstoccurrence between to_date('<pStart>','dd.mm.yyyy') and to_date('<pEnd>','dd.mm.yyyy')
--and r.firstoccurrence between t.firstoccurrence and  t.cleartime
and t.class in ( 8891, 5010, 74000)
and t.region<> 'Default'
and t.CLEARTIME >= to_date('<pStart>','dd.mm.yyyy')
and t.specificproblem in ('BTS EXTERNAL FAULT')
group by r.region, r.node, substr(t.nodealias,0,6), t.firstoccurrence, r.firstoccurrence, r.cleartime
order by 2",
                //                'width' => array ('eventid'=>50,'name'=>300,'solution'=>30,'message'=>200, 'type'=>40),
                'enrich' => array(
                    'db' => 'cramer_admin',
                    'sql' => "select a2.loc,sum(a2.node) as swith_count,listagg(a2.mdu,',') within group (order by 1) as mdu, count(a2.mdu) as mdu_count,sum(a2.service) as service_count from (
select a1.loc,count(distinct a1.nodeid) as node,a1.mdu,count(cc.circuitid) as service from (
SELECT 
  nvl(sl.atoll_site_name,SUBSTR(l.objectid, 1, INSTR(l.objectid, ';') - 1)) AS loc,
  nn.nodeid,substr(nn.name,0,12) as mdu
FROM CRAMER.location_o l
JOIN CRAMER.sattab_locationsite_o sl ON l.locationid = sl.locationid
JOIN CRAMER.node_o n ON l.locationid = n.node2location and n.node2nodetype IN (1900000077, 1900000186, 1900000078, 1900000079)
JOIN CRAMER.port_v pv on n.nodeid = pv.PORT2NODE and (pv.description LIKE 'MDU%' or pv.description LIKE 'PON%')
left join cramer.port_v pp on pp.PORT2ROOTPORT=pv.PORTID
left join circuit_o qq on pp.PORTID in (qq.circuit2startport,qq.circuit2endport) and qq.circuit2circuitdef=1900000036 and qq.alias1='LLDP'
left join port_v qp on qp.PORTID in (qq.circuit2startport,qq.circuit2endport)
left join node_o qn on qn.nodeid=qp.PORT2NODE and qn.node2nodetype not in (1900000077, 1900000186, 1900000078, 1900000079)
left join node_o nn on substr(nn.name,0,15)=substr(qn.name,0,15) and nn.name not like '%ODF%'
group by nvl(sl.atoll_site_name,SUBSTR(l.objectid, 1, INSTR(l.objectid, ';') - 1)),nn.nodeid,substr(nn.name,0,12)
) a1
left join circuit_o cc on a1.nodeid in (cc.circuit2startnode,cc.circuit2endnode) and cc.circuit2circuitdef=1900000043
where a1.loc='<NODEALIAS>'
group by a1.loc,a1.mdu
) a2
group by a2.loc"
                ),
                'hiden_fields' => array('LOC')
            ),
        )
    ),
);
