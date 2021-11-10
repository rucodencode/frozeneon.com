SELECT hour(`time_created`) as `hour`, `object_id`, SUM(`amount`) as `sum` FROM `analytics` WHERE `object` = 'boosterpack' GROUP BY `hour`, `object_id`;
