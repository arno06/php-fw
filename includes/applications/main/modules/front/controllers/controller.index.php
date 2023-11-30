<?php
namespace app\main\controllers\front
{
    use core\application\DefaultController;
    use core\db\Query;
    use core\tools\debugger\Debugger;

    class index extends DefaultController
    {

        public function __construct()
        {

        }

        public function demotech(){
            $query = <<<QUE
SELECT
  p.productId as id,
  p.nameWithoutAccent as name,
  p.marketStatus,
  'drugs' as type,
  cg.publicNameWithoutAccent as vmp_name,
  cg.commonNameGroupId as vmp_id,
  vidalClasses.parent0classId,
  vidalClasses.parent0name,
  vidalClasses.parent1classId,
  vidalClasses.parent1name,
  vidalClasses.parent2classId,
  vidalClasses.parent2name,
  vidalClasses.parent3classId,
  vidalClasses.parent3name,
  vidalClasses.parent4classId,
  vidalClasses.parent4name,
  vidalClasses.parent5classId,
  vidalClasses.parent5name,
  (
    select
      max(severity)
    from
      interaction i
    where
      i.druginteractionclassId1 IN (
        586, 767, 767, 767, 773, 773, 773, 774,
        774, 775, 776, 776, 776, 779, 779, 970,
        1056, 1088, 1088, 1088, 1104, 1444,
        1444
      )
      and i.druginteractionclassId2 IN (
        SELECT
          cgdi.drugInteractionClassId
        from
          commonnamegroup_druginteractionclass as cgdi
        where
          cgdi.commonnamegroupId = cg.commonnamegroupId
      )
  ) as max_severity
FROM
  commonnamegroup AS cg
  INNER JOIN product AS p ON p.commonNameGroupId = cg.commonNameGroupId
  INNER JOIN product_vidal AS pv ON pv.productId = p.productId
  LEFT JOIN (
    SELECT
      v0.vidalClassId as parent0classId,
      v0.name as parent0name,
      v0.parentId as parent1classId,
      v1.name as parent1name,
      v1.parentId as parent2classId,
      v2.name as parent2name,
      v2.parentId as parent3classId,
      v3.name as parent3name,
      v3.parentId as parent4classId,
      v4.name as parent4name,
      v4.parentId as parent5classId,
      v5.name as parent5name
    FROM
      product_vidal pv
      LEFT JOIN vidalclass v0 ON v0.vidalClassId = pv.vidalClassId
      LEFT JOIN vidalclass v1 ON v1.vidalClassId = v0.parentId
      LEFT JOIN vidalclass v2 ON v2.vidalClassId = v1.parentId
      LEFT JOIN vidalclass v3 ON v3.vidalClassId = v2.parentId
      LEFT JOIN vidalclass v4 ON v4.vidalClassId = v3.parentId
      LEFT JOIN vidalclass v5 ON v5.vidalClassId = v4.parentId
  ) As vidalClasses ON vidalClasses.parent0classId = pv.vidalClassId
WHERE
  (
    p.marketStatus = '0'
    OR p.marketStatus = '3'
  )
  AND (
    vidalClasses.parent1classId = 813
    OR vidalClasses.parent2classId = 813
    OR vidalClasses.parent3classId = 813
    OR vidalClasses.parent4classId = 813
    OR vidalClasses.parent5classId = 813
  )
GROUP BY
  p.productId
ORDER BY
  max_severity,
  parent0name,
  vmp_name,
  p.nameWithoutAccent ASC
QUE;

            $queryR = <<<QUE
SELECT
  p.productId as id,
  p.nameWithoutAccent as name,
  p.marketStatus,
  'drugs' as type,
  cg.publicNameWithoutAccent as vmp_name,
  cg.commonNameGroupId as vmp_id,
  vidalClasses.parent0classId,
  vidalClasses.parent0name,
  vidalClasses.parent1classId,
  vidalClasses.parent1name,
  vidalClasses.parent2classId,
  vidalClasses.parent2name,
  vidalClasses.parent3classId,
  vidalClasses.parent3name,
  vidalClasses.parent4classId,
  vidalClasses.parent4name,
  vidalClasses.parent5classId,
  vidalClasses.parent5name,
  (
    select
      max(severity)
    from
      interaction i
    where
      i.druginteractionclassId1 IN (
        586, 767, 767, 767, 773, 773, 773, 774,
        774, 775, 776, 776, 776, 779, 779, 970,
        1056, 1088, 1088, 1088, 1104, 1444,
        1444
      )
      and i.druginteractionclassId2 IN (
        SELECT
          cgdi.drugInteractionClassId
        from
          commonnamegroup_druginteractionclass as cgdi
        where
          cgdi.commonnamegroupId = cg.commonnamegroupId
      )
  ) as max_severity
FROM
  product AS p 
  INNER JOIN product_vidal AS pv ON pv.productId = p.productId
  INNER JOIN commonnamegroup AS cg ON p.commonNameGroupId = cg.commonNameGroupId
  LEFT JOIN (
    SELECT
      v0.vidalClassId as parent0classId,
      v0.name as parent0name,
      v0.parentId as parent1classId,
      v1.name as parent1name,
      v1.parentId as parent2classId,
      v2.name as parent2name,
      v2.parentId as parent3classId,
      v3.name as parent3name,
      v3.parentId as parent4classId,
      v4.name as parent4name,
      v4.parentId as parent5classId,
      v5.name as parent5name
    FROM
      vidalclass v0
      LEFT JOIN vidalclass v1 ON v1.vidalClassId = v0.parentId
      LEFT JOIN vidalclass v2 ON v2.vidalClassId = v1.parentId
      LEFT JOIN vidalclass v3 ON v3.vidalClassId = v2.parentId
      LEFT JOIN vidalclass v4 ON v4.vidalClassId = v3.parentId
      LEFT JOIN vidalclass v5 ON v5.vidalClassId = v4.parentId
  ) As vidalClasses ON vidalClasses.parent0classId = pv.vidalClassId
WHERE
  (
    p.marketStatus = '0'
    OR p.marketStatus = '3'
  )
  AND (
    vidalClasses.parent1classId = 813
    OR vidalClasses.parent2classId = 813
    OR vidalClasses.parent3classId = 813
    OR vidalClasses.parent4classId = 813
    OR vidalClasses.parent5classId = 813
  )
GROUP BY
  p.productId
ORDER BY
  max_severity,
  parent0name,
  vmp_name,
  p.nameWithoutAccent ASC
QUE;

            $query1 = <<<QUE
SELECT
  p.productId as id,
  p.nameWithoutAccent as name,
  p.marketStatus,
  'drugs' as type,
  cg.publicNameWithoutAccent as vmp_name,
  cg.commonNameGroupId as vmp_id,
  vidalClasses.parent0classId,
  vidalClasses.parent0name,
  vidalClasses.parent1classId,
  vidalClasses.parent1name,
  vidalClasses.parent2classId,
  vidalClasses.parent2name,
  vidalClasses.parent3classId,
  vidalClasses.parent3name,
  vidalClasses.parent4classId,
  vidalClasses.parent4name,
  vidalClasses.parent5classId,
  vidalClasses.parent5name,
  (
    select
      max(severity)
    from
      interaction i
    where
      i.druginteractionclassId1 IN (30, 1088, 1104)
      and i.druginteractionclassId2 IN (
        SELECT
          cgdi.drugInteractionClassId
        from
          commonnamegroup_druginteractionclass as cgdi
        where
          cgdi.commonnamegroupId = cg.commonnamegroupId
      )
  ) as max_severity
FROM
  commonnamegroup AS cg
  INNER JOIN product AS p ON p.commonNameGroupId = cg.commonNameGroupId
  INNER JOIN product_vidal AS pv ON pv.productId = p.productId
  LEFT JOIN (
    SELECT
      v0.vidalClassId as parent0classId,
      v0.name as parent0name,
      v0.parentId as parent1classId,
      v1.name as parent1name,
      v1.parentId as parent2classId,
      v2.name as parent2name,
      v2.parentId as parent3classId,
      v3.name as parent3name,
      v3.parentId as parent4classId,
      v4.name as parent4name,
      v4.parentId as parent5classId,
      v5.name as parent5name
    FROM
      product_vidal pv
      LEFT JOIN vidalclass v0 ON v0.vidalClassId = pv.vidalClassId
      LEFT JOIN vidalclass v1 ON v1.vidalClassId = v0.parentId
      LEFT JOIN vidalclass v2 ON v2.vidalClassId = v1.parentId
      LEFT JOIN vidalclass v3 ON v3.vidalClassId = v2.parentId
      LEFT JOIN vidalclass v4 ON v4.vidalClassId = v3.parentId
      LEFT JOIN vidalclass v5 ON v5.vidalClassId = v4.parentId
  ) As vidalClasses ON vidalClasses.parent0classId = pv.vidalClassId
WHERE
  (
    p.marketStatus = '0'
    OR p.marketStatus = '3'
  )
  AND (
    vidalClasses.parent1classId = 4499
    OR vidalClasses.parent2classId = 4499
    OR vidalClasses.parent3classId = 4499
    OR vidalClasses.parent4classId = 4499
    OR vidalClasses.parent5classId = 4499
  )
GROUP BY
  p.productId
ORDER BY
  max_severity,
  parent0name,
  vmp_name,
  p.nameWithoutAccent ASC
QUE;

            $query2 = <<<QUE
SELECT
  p.productId as id,
  p.nameWithoutAccent as name,
  p.marketStatus,
  'drugs' as type,
  cg.publicNameWithoutAccent as vmp_name,
  cg.commonNameGroupId as vmp_id,
  vidalClasses.parent0classId,
  vidalClasses.parent0name,
  vidalClasses.parent1classId,
  vidalClasses.parent1name,
  vidalClasses.parent2classId,
  vidalClasses.parent2name,
  vidalClasses.parent3classId,
  vidalClasses.parent3name,
  vidalClasses.parent4classId,
  vidalClasses.parent4name,
  vidalClasses.parent5classId,
  vidalClasses.parent5name,
  (
    select
      max(severity)
    from
      interaction i
    where
      i.druginteractionclassId1 IN (30, 1088, 1104)
      and i.druginteractionclassId2 IN (
        SELECT
          cgdi.drugInteractionClassId
        from
          commonnamegroup_druginteractionclass as cgdi
        where
          cgdi.commonnamegroupId = cg.commonnamegroupId
      )
  ) as max_severity
FROM
  commonnamegroup AS cg
  INNER JOIN product AS p ON p.commonNameGroupId = cg.commonNameGroupId
  INNER JOIN product_vidal AS pv ON pv.productId = p.productId
  LEFT JOIN (
    SELECT
      v0.vidalClassId as parent0classId,
      v0.name as parent0name,
      v0.parentId as parent1classId,
      v1.name as parent1name,
      v1.parentId as parent2classId,
      v2.name as parent2name,
      v2.parentId as parent3classId,
      v3.name as parent3name,
      v3.parentId as parent4classId,
      v4.name as parent4name,
      v4.parentId as parent5classId,
      v5.name as parent5name
    FROM
      vidalclass v0
      LEFT JOIN vidalclass v1 ON v1.vidalClassId = v0.parentId
      LEFT JOIN vidalclass v2 ON v2.vidalClassId = v1.parentId
      LEFT JOIN vidalclass v3 ON v3.vidalClassId = v2.parentId
      LEFT JOIN vidalclass v4 ON v4.vidalClassId = v3.parentId
      LEFT JOIN vidalclass v5 ON v5.vidalClassId = v4.parentId
  ) As vidalClasses ON vidalClasses.parent0classId = pv.vidalClassId
WHERE
  (
    p.marketStatus = '0'
    OR p.marketStatus = '3'
  )
  AND (
    vidalClasses.parent1classId = 4499
    OR vidalClasses.parent2classId = 4499
    OR vidalClasses.parent3classId = 4499
    OR vidalClasses.parent4classId = 4499
    OR vidalClasses.parent5classId = 4499
  )
GROUP BY
  p.productId
ORDER BY
  max_severity,
  parent0name,
  vmp_name,
  p.nameWithoutAccent ASC
QUE;
            $query3 = <<<QUE
SELECT
  p.productId as id,
  p.nameWithoutAccent as name,
  p.marketStatus,
  'drugs' as type,
  cg.publicNameWithoutAccent as vmp_name,
  cg.commonNameGroupId as vmp_id,
  vidalClasses.parent0classId,
  vidalClasses.parent0name,
  vidalClasses.parent1classId,
  vidalClasses.parent1name,
  vidalClasses.parent2classId,
  vidalClasses.parent2name,
  vidalClasses.parent3classId,
  vidalClasses.parent3name,
  vidalClasses.parent4classId,
  vidalClasses.parent4name,
  vidalClasses.parent5classId,
  vidalClasses.parent5name,
  (
    select
      max(severity)
    from
      interaction i
    where
      i.druginteractionclassId1 IN (30, 1088, 1104)
      and i.druginteractionclassId2 IN (
        SELECT
          cgdi.drugInteractionClassId
        from
          commonnamegroup_druginteractionclass as cgdi
        where
          cgdi.commonnamegroupId = cg.commonnamegroupId
      )
  ) as max_severity
FROM
  product AS p
  INNER JOIN commonnamegroup AS cg ON p.commonNameGroupId = cg.commonNameGroupId
  INNER JOIN product_vidal AS pv ON pv.productId = p.productId
  LEFT JOIN (
    SELECT
      v0.vidalClassId as parent0classId,
      v0.name as parent0name,
      v0.parentId as parent1classId,
      v1.name as parent1name,
      v1.parentId as parent2classId,
      v2.name as parent2name,
      v2.parentId as parent3classId,
      v3.name as parent3name,
      v3.parentId as parent4classId,
      v4.name as parent4name,
      v4.parentId as parent5classId,
      v5.name as parent5name
    FROM
      vidalclass v0
      LEFT JOIN vidalclass v1 ON v1.vidalClassId = v0.parentId
      LEFT JOIN vidalclass v2 ON v2.vidalClassId = v1.parentId
      LEFT JOIN vidalclass v3 ON v3.vidalClassId = v2.parentId
      LEFT JOIN vidalclass v4 ON v4.vidalClassId = v3.parentId
      LEFT JOIN vidalclass v5 ON v5.vidalClassId = v4.parentId
  ) As vidalClasses ON vidalClasses.parent0classId = pv.vidalClassId
WHERE
  (
    p.marketStatus = '0'
    OR p.marketStatus = '3'
  )
  AND (
    vidalClasses.parent1classId = 4499
    OR vidalClasses.parent2classId = 4499
    OR vidalClasses.parent3classId = 4499
    OR vidalClasses.parent4classId = 4499
    OR vidalClasses.parent5classId = 4499
  )
GROUP BY
  p.productId
ORDER BY
  max_severity,
  parent0name,
  vmp_name,
  p.nameWithoutAccent ASC
QUE;

            $query4 = <<<QUE
SELECT
  p.productId as id,
  p.nameWithoutAccent as name,
  p.marketStatus,
  'drugs' as type,
  cg.publicNameWithoutAccent as vmp_name,
  cg.commonNameGroupId as vmp_id,
  vidalClasses.parent0classId,
  vidalClasses.parent0name,
  vidalClasses.parent1classId,
  vidalClasses.parent1name,
  vidalClasses.parent2classId,
  vidalClasses.parent2name,
  vidalClasses.parent3classId,
  vidalClasses.parent3name,
  vidalClasses.parent4classId,
  vidalClasses.parent4name,
  vidalClasses.parent5classId,
  vidalClasses.parent5name,
  (
    select
      max(severity)
    from
      interaction i
    where
      i.druginteractionclassId1 IN (30, 1088, 1104)
      and i.druginteractionclassId2 IN (
        SELECT
          cgdi.drugInteractionClassId
        from
          commonnamegroup_druginteractionclass as cgdi
        where
          cgdi.commonnamegroupId = p.commonnamegroupId
      )
  ) as max_severity
FROM
  product AS p
  INNER JOIN commonnamegroup AS cg ON p.commonNameGroupId = cg.commonNameGroupId
  INNER JOIN product_vidal AS pv ON pv.productId = p.productId
  LEFT JOIN (
    SELECT
      v0.vidalClassId as parent0classId,
      v0.name as parent0name,
      v0.parentId as parent1classId,
      v1.name as parent1name,
      v1.parentId as parent2classId,
      v2.name as parent2name,
      v2.parentId as parent3classId,
      v3.name as parent3name,
      v3.parentId as parent4classId,
      v4.name as parent4name,
      v4.parentId as parent5classId,
      v5.name as parent5name
    FROM
      vidalclass v0
      LEFT JOIN vidalclass v1 ON v1.vidalClassId = v0.parentId
      LEFT JOIN vidalclass v2 ON v2.vidalClassId = v1.parentId
      LEFT JOIN vidalclass v3 ON v3.vidalClassId = v2.parentId
      LEFT JOIN vidalclass v4 ON v4.vidalClassId = v3.parentId
      LEFT JOIN vidalclass v5 ON v5.vidalClassId = v4.parentId
  ) As vidalClasses ON vidalClasses.parent0classId = pv.vidalClassId
WHERE
  (
    p.marketStatus = '0'
    OR p.marketStatus = '3'
  )
  AND (
    vidalClasses.parent1classId = 4499
    OR vidalClasses.parent2classId = 4499
    OR vidalClasses.parent3classId = 4499
    OR vidalClasses.parent4classId = 4499
    OR vidalClasses.parent5classId = 4499
  )
GROUP BY
  p.productId
ORDER BY
  max_severity,
  parent0name,
  vmp_name,
  p.nameWithoutAccent ASC
QUE;
            //@todo
            $query5 = <<<QUE
SELECT
  p.productId as id,
  p.nameWithoutAccent as name,
  p.marketStatus,
  'drugs' as type,
  cg.publicNameWithoutAccent as vmp_name,
  cg.commonNameGroupId as vmp_id,
  v.name as parent0name,
  v.vidalClassId as parent0classId,
  (
    select
      max(severity)
    from
      interaction i
    where
      i.druginteractionclassId1 IN (30, 1088, 1104)
      and i.druginteractionclassId2 IN (
        SELECT
          cgdi.drugInteractionClassId
        from
          commonnamegroup_druginteractionclass as cgdi
        where
          cgdi.commonnamegroupId = cg.commonnamegroupId
      )
  ) as max_severity
FROM
  commonnamegroup AS cg
  INNER JOIN product AS p ON p.commonNameGroupId = cg.commonNameGroupId
  INNER JOIN product_vidal AS pv ON pv.productId = p.productId
  INNER JOIN vidalclass v ON v.vidalClassId = pv.vidalClassId
WHERE
  (
    p.marketStatus = '0'
    OR p.marketStatus = '3'
  )
  AND pv.vidalClassId = 155
GROUP BY
  p.productId
ORDER BY
  max_severity,
  parent0name,
  vmp_name,
  p.nameWithoutAccent ASC
QUE;



            //$this->testQuery('withoutIndex', $query, 'mobile', false, false);
            //$this->testQuery('withIndex', $query, 'mobile2', false, false);
            //$this->testQuery('withIndexAndOpti', $queryR, 'mobile2', false, false);

            /*$this->testQuery('1_withIndex', $query1, 'mobile2', false, false);

            $this->testQuery('2_withIndex', $query2, 'mobile2', false, false);

            $this->testQuery('3_withIndex', $query3, 'mobile2', false, false);

            $this->testQuery('4_withIndex', $query4, 'mobile2', false, false);*/

            $this->testQuery('3_withoutIndex', $query3, 'mobile', false, false);

            //$this->testQuery('4_withIndex3', $query4, 'mobile3', false, false);

            $this->testQuery('4_withIndex3', $query4, 'mobile3', false, false);

            /*trace_r(Query::execute("SELECT * FROM sqlite_master WHERE type = 'index';", "mobile2"));
            trace_r(Query::execute("select * from vidalclass", "mobile2"));*/
        }

        public function prepare(){

            /*Query::execute('CREATE INDEX id1 ON interaction(druginteractionclassId1)', "mobile2");
            Query::execute('CREATE INDEX id2 ON interaction(druginteractionclassId2)', "mobile2");
            Query::execute('CREATE INDEX cgdic_cgi ON commonnamegroup_druginteractionclass(commonnamegroupId)', 'mobile2');*/
            //Query::execute('CREATE INDEX pvpid ON product_vidal(productId)', 'mobile2');
            //Query::execute('CREATE INDEX pvcid ON product_vidal(vidalClassId)', 'mobile2');

            Query::execute('CREATE INDEX id1_2 ON interaction(druginteractionclassId1, druginteractionclassId2)', "mobile3");
        }

        private function testQuery($pLabel, $pQuery, $pHandler, $pTraceResult = false, $pExplainPlan = false){
            track($pLabel);
            $res = Query::execute($pQuery, $pHandler);
            track($pLabel);
            if($pTraceResult){
                trace_r($res);
            }
            if(is_array($res)){
                trace_r($pLabel." :: count ".count($res));
            }else{
                trace($pLabel." no results");
            }
            if($pExplainPlan){
                trace_r(Query::execute("EXPLAIN QUERY PLAN ".$pQuery, $pHandler));
            }
        }
    }
}
