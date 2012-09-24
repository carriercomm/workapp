<?php
class Model_Route extends Modules_Model {
	private $_tasks = array();
	private $_step_id = 0;
	private $_tid = 0;
	private $_rid =  0;

	// Draft
	function getRouteIdFromStep_id($step_id) {
		$sql = "SELECT rid FROM draft_route_route_tasks WHERE step_id = :step_id LIMIT 1";
		
		$res = $this->registry['db']->prepare($sql);
		$param = array(":step_id" => $step_id);
		$res->execute($param);
		$data = $res->fetchAll(PDO::FETCH_ASSOC);
		
		return $data[0]["rid"];
	}
	
	function getStepFromRoute($rid) {
		$sql = "SELECT drs.id, drs.name, drs.order
		FROM draft_route_route_tasks AS drrt
		LEFT JOIN draft_route_step AS drs ON (drs.id = drrt.step_id)
		WHERE drrt.rid = :rid
		GROUP BY drs.id
		ORDER BY drs.order";
		
		$res = $this->registry['db']->prepare($sql);
		$param = array(":rid" => $rid);
		$res->execute($param);
		$data = $res->fetchAll(PDO::FETCH_ASSOC);
		
		return $data;
	}
	
	function setDraftRouteName($rid, $name) {
		$sql = "UPDATE draft_route SET name = :name WHERE id = :rid";
		
		$res = $this->registry['db']->prepare($sql);
		$param = array(":rid" => $rid, ":name" => $name);
		$res->execute($param);
	}
	
	function getDraftRoutes() {
		$sql = "SELECT id, name FROM draft_route ORDER BY id DESC";
		
		$res = $this->registry['db']->prepare($sql);
		$param = array();
		$res->execute($param);
		$rows = $res->fetchAll(PDO::FETCH_ASSOC);
		
		return $rows;
	}
	
	function addDraftStep($rid) {
		$sql = "SELECT MAX(drs.order) AS max
		FROM draft_route_step AS drs
		LEFT JOIN draft_route_route_tasks AS drrt ON (drrt.step_id = drs.id)
		WHERE drrt.rid = :rid";
		
		$res = $this->registry['db']->prepare($sql);
		$param = array(":rid" => $rid);
		$res->execute($param);
		$max = $res->fetchAll(PDO::FETCH_ASSOC);
		
		$sql = "INSERT INTO draft_route_step (`order`, `name`) VALUES (:order, :name)";
		
		$res = $this->registry['db']->prepare($sql);
		$param = array(":order" => ($max[0]["max"] + 1), ":name" => "Новый этап");
		$res->execute($param);
		
		$this->_step_id = $this->registry['db']->lastInsertId();
		
		$this->addDraftRouteTask($rid, $this->_step_id);
	}
	
	function addDraftStepBefore($rid, $step_id) {
		$last_order = $this->getDraftLastStepOrder($rid);
		
		$this->addDraftStep($rid);
		
		$new_id = $this->getStep_id();
		$sql = "SELECT `order` FROM draft_route_step WHERE id = :id LIMIT 1";
		
		$res = $this->registry['db']->prepare($sql);
		$param = array(":id" => $step_id);
		$res->execute($param);
		$data = $res->fetchAll(PDO::FETCH_ASSOC);
		$new_order = $data[0]["order"];

		for($i=$last_order; $i>=$new_order; $i--) { echo '!';
			$sql = "UPDATE draft_route_step SET `order` = `order`+1 WHERE `order` = :order";

			$res = $this->registry['db']->prepare($sql);
			$param = array(":order" => $i);
			$res->execute($param);
		}
		
		$sql = "UPDATE draft_route_step SET `order` = :order WHERE id = :step_id";

		$res = $this->registry['db']->prepare($sql);
		$param = array(":step_id" => $new_id, ":order" => $new_order);
		$res->execute($param);
	}

	function getDraftLastStepOrder($rid) {
		$sql = "SELECT MAX(drs.order) AS max
		FROM draft_route_step AS drs
		LEFT JOIN draft_route_route_tasks AS drrt ON (drrt.step_id = drs.id)
		WHERE drrt.rid = :rid";
		
		$res = $this->registry['db']->prepare($sql);
		$param = array(":rid" => $rid);
		$res->execute($param);
		$data = $res->fetchAll(PDO::FETCH_ASSOC);
		
		return $data[0]["max"];
	}
	
	function delDraftStep($step_id) {
		$sql = "SELECT tid FROM draft_route_route_tasks WHERE step_id = :step_id";
		
		$res = $this->registry['db']->prepare($sql);
		$param = array(":step_id" => $step_id);
		$res->execute($param);
		$data = $res->fetchAll(PDO::FETCH_ASSOC);
		
		foreach($data as $part) {
			$sql = "DELETE FROM draft_route_tasks WHERE id = :tid";
			
			$res = $this->registry['db']->prepare($sql);
			$param = array(":tid" => $part["tid"]);
			$res->execute($param);
		}
		
		$sql = "DELETE FROM draft_route_step WHERE id = :step_id";
		
		$res = $this->registry['db']->prepare($sql);
		$param = array(":step_id" => $step_id);
		$res->execute($param);
		
		$sql = "DELETE FROM draft_route_route_tasks WHERE step_id = :step_id";
		
		$res = $this->registry['db']->prepare($sql);
		$param = array(":step_id" => $step_id);
		$res->execute($param);
	}
	
	function renameDraftStep($step_id, $name) {
		$sql = "UPDATE draft_route_step SET name = :name WHERE id = :step_id";
		
		$res = $this->registry['db']->prepare($sql);
		$param = array(":step_id" => $step_id, ":name" => $name);
		$res->execute($param);
	}
	
	function getStep_id() {
		return $this->_step_id;
	}
	
	function getTid() {
		return $this->_tid;
	}
	
	function addDraftRoute($name) {
		$sql = "INSERT INTO draft_route (name) VALUES (:name)";
		
		$res = $this->registry['db']->prepare($sql);
		$param = array(":name" => $name);
		$res->execute($param);
		
		$rid = $this->registry['db']->lastInsertId();
		
		$this->addDraftStep($rid);
		
		return $rid;
	}
	
	function setDraftRoute() {
	
	}
	
	function getDraftRoute($id) {
		$this->_rid = $id;
		
		$sql = "SELECT id, name FROM draft_route WHERE id = :id LIMIT 1";
		
		$res = $this->registry['db']->prepare($sql);
		$param = array(":id" => $id);
		$res->execute($param);
		$rows = $res->fetchAll(PDO::FETCH_ASSOC);

		return $rows;
	}
	
	function getDraftSteps() {
		$sql = "SELECT drrt.step_id, drs.name
				FROM draft_route_route_tasks AS drrt
				LEFT JOIN draft_route_step AS drs ON (drs.id = drrt.step_id)
				WHERE drrt.rid = :rid
				GROUP BY drrt.step_id
				ORDER BY drs.order, drrt.step_id";
		
		$res = $this->registry['db']->prepare($sql);
		$param = array(":rid" => $this->_rid);
		$res->execute($param);
		return $step = $res->fetchAll(PDO::FETCH_ASSOC);
	}
	
	function getTasks($rid) {
		$sql = "SELECT tid, step_id FROM draft_route_route_tasks WHERE rid = :rid ORDER BY step_id";
		
		$res = $this->registry['db']->prepare($sql);
		$param = array(":rid" => $rid);
		$res->execute($param);
		$step = $res->fetchAll(PDO::FETCH_ASSOC);
		
		$result = array(); $i=0;
		
		foreach($step as $part) {
			$sql = "SELECT id AS tid, json FROM draft_route_tasks WHERE id = :id LIMIT 1";
			
			$res = $this->registry['db']->prepare($sql);
			$param = array(":id" => $part["tid"]);
			$res->execute($param);
			$data = $res->fetchAll(PDO::FETCH_ASSOC);
			
			$result[$i] = $data[0];
			$result[$i]["rid"] = $rid;
			$result[$i]["step_id"] = $part["step_id"];
			$result[$i]["task"] = json_decode($data[0]["json"], true);
			
			$i++;
		}
		
		return $result;
	}
	
	function delDraftRoute($id) {
		$sql = "SELECT tid, step_id FROM draft_route_route_tasks WHERE rid = :rid";
			
		$res = $this->registry['db']->prepare($sql);
		$param = array(":rid" => $id);
		$res->execute($param);
		$data = $res->fetchAll(PDO::FETCH_ASSOC);
		
		foreach($data as $part) {
			$sql = "DELETE FROM draft_route_tasks WHERE id = :id LIMIT 1";
			
			$res = $this->registry['db']->prepare($sql);
			$param = array(":id" => $part["tid"]);
			$res->execute($param);
			
			$sql = "DELETE FROM draft_route_step WHERE id = :id LIMIT 1";
			
			$res = $this->registry['db']->prepare($sql);
			$param = array(":id" => $part["step_id"]);
			$res->execute($param);
		}
		
		$sql = "DELETE FROM draft_route_route_tasks WHERE rid = :rid";
			
		$res = $this->registry['db']->prepare($sql);
		$param = array(":rid" => $id);
		$res->execute($param);
		
		$sql = "DELETE FROM draft_route WHERE id = :id LIMIT 1";
		
		$res = $this->registry['db']->prepare($sql);
		$param = array(":id" => $id);
		$res->execute($param);
	}
	
	
	function addDraftRouteTask($rid, $step_id) {
		$sql = "INSERT INTO draft_route_tasks (json) VALUES (:json)";
		
		$res = $this->registry['db']->prepare($sql);
		$param = array(":json" => "");
		$res->execute($param);
		
		$this->_tid = $this->registry['db']->lastInsertId();
		
		$sql = "INSERT INTO draft_route_route_tasks (rid, tid, step_id) VALUES (:rid, :tid, :step_id)";
		
		$res = $this->registry['db']->prepare($sql);
		$param = array(":tid" => $this->_tid, ":rid" => $rid, ":step_id" => $step_id);
		$res->execute($param);
	}
	
	function getResult($tid) {
		$sql = "SELECT id, name, type, datatype FROM draft_route_tasks_results WHERE tid = :tid";
		
		$res = $this->registry['db']->prepare($sql);
		$param = array(":tid" => $tid);
		$res->execute($param);
		$data = $res->fetchAll(PDO::FETCH_ASSOC);
		
		return $data;
	}
	
	function getTaskData($tid) {
		$sql = "SELECT drrt.step_id, drrt.rid, drs.order
		FROM draft_route_route_tasks AS drrt
		LEFT JOIN draft_route_step AS drs ON (drs.id = drrt.step_id)
		WHERE drrt.tid = :tid LIMIT 1";
		
		$res = $this->registry['db']->prepare($sql);
		$param = array(":tid" => $tid);
		$res->execute($param);
		$data = $res->fetchAll(PDO::FETCH_ASSOC);

		$sql = "SELECT drrt.tid, drs.name, drt.json
		FROM draft_route_route_tasks AS drrt
		LEFT JOIN draft_route_step AS drs ON (drs.id = drrt.step_id)
		LEFT JOIN draft_route_tasks AS drt ON (drt.id = drrt.tid)
		WHERE drrt.rid = :rid AND drs.order < :order";
		
		$res = $this->registry['db']->prepare($sql);
		$param = array(":rid" => $data[0]["rid"], "order" => $data[0]["order"]);
		$res->execute($param);
		$data = $res->fetchAll(PDO::FETCH_ASSOC);
		
		$result = array(); $i=0;
		
		foreach($data as $part) {
			$sql = "SELECT id, name, type, datatype
			FROM draft_route_tasks_results
			WHERE tid = :tid";
			
			$res = $this->registry['db']->prepare($sql);
			$param = array(":tid" => $part["tid"]);
			$res->execute($param);
			$result[$i] = $res->fetchAll(PDO::FETCH_ASSOC);
			
			$result[$i]["step_name"] = $part["name"];
			$task = json_decode($part["json"], true);
			$result[$i]["task_name"] = $task["taskname"];
			
			$i++;
		}
		
		return $result;
	}
	
	function getStepData($step_id) {
		$sql = "SELECT drrt.step_id, drrt.rid, drs.order
			FROM draft_route_route_tasks AS drrt
			LEFT JOIN draft_route_step AS drs ON (drs.id = drrt.step_id)
			WHERE drrt.step_id = :step_id LIMIT 1";
	
		$res = $this->registry['db']->prepare($sql);
		$param = array(":step_id" => $step_id);
		$res->execute($param);
		$data = $res->fetchAll(PDO::FETCH_ASSOC);
	
		$sql = "SELECT drrt.tid, drs.name, drt.json
			FROM draft_route_route_tasks AS drrt
			LEFT JOIN draft_route_step AS drs ON (drs.id = drrt.step_id)
			LEFT JOIN draft_route_tasks AS drt ON (drt.id = drrt.tid)
			WHERE drrt.rid = :rid AND drs.order <= :order";
	
		$res = $this->registry['db']->prepare($sql);
		$param = array(":rid" => $data[0]["rid"], "order" => $data[0]["order"]);
		$res->execute($param);
		$data = $res->fetchAll(PDO::FETCH_ASSOC);
	
		$result = array(); $i=0;
	
		foreach($data as $part) {
			$sql = "SELECT id, name, type, datatype
				FROM draft_route_tasks_results
				WHERE tid = :tid";
				
			$res = $this->registry['db']->prepare($sql);
			$param = array(":tid" => $part["tid"]);
			$res->execute($param);
			$result[$i] = $res->fetchAll(PDO::FETCH_ASSOC);
				
			$result[$i]["step_name"] = $part["name"];
			$task = json_decode($part["json"], true);
			$result[$i]["task_name"] = $task["taskname"];
				
			$i++;
		}
	
		return $result;
	}
	
	function setDraftRouteTask($tid, $task, $uid = 0) {
		if ($uid == 0) {
			$uid = $this->registry["ui"]["id"];
		}
		
		$sql = "UPDATE draft_route_tasks SET uid = :uid, json = :json WHERE id = :tid";
		
		$res = $this->registry['db']->prepare($sql);
		$param = array(":tid" => $tid, ":json" => json_encode($task), ":uid" => $uid);
		$res->execute($param);

		for($i=0; $i<count($task["field"]); $i++) {
			if (!isset($task["res_id"][$i])) {
				$sql = "INSERT INTO draft_route_tasks_results (tid, name, type, datatype) VALUES (:tid, :name, :type, :datatype)";
				
				$res = $this->registry['db']->prepare($sql);
				$param = array(":tid" => $tid, ":name" => $task["field"][$i], ":type" => $task["res_type"][$i], ":datatype" => $task["datatype"][$i]);
				$res->execute($param);
			} else {
				if ($task["field"][$i] == "") {
					$sql = "DELETE FROM draft_route_tasks_results WHERE id = :id LIMIT 1";
					
					$res = $this->registry['db']->prepare($sql);
					$param = array(":id" => $task["res_id"][$i]);
					$res->execute($param);
				} else {
					$sql = "UPDATE draft_route_tasks_results SET tid = :tid, name = :name, type = :type, datatype = :datatype WHERE id = :id LIMIT 1";
					
					$res = $this->registry['db']->prepare($sql);
					$param = array(":id" => $task["res_id"][$i], ":tid" => $tid, ":name" => $task["field"][$i], ":type" => $task["res_type"][$i], ":datatype" => $task["datatype"][$i]);
					$res->execute($param);
				}
			}
		}
	}
	
	function getDraftRouteTask($tid) {
		$sql = "SELECT drt.id AS tid, drt.json, drrt.rid, drrt.step_id
		FROM draft_route_tasks AS drt
		LEFT JOIN draft_route_route_tasks AS drrt ON (drrt.tid = drt.id)
		WHERE drt.id = :id";
			
		$res = $this->registry['db']->prepare($sql);
		$param = array(":id" => $tid);
		$res->execute($param);
		$data = $res->fetchAll(PDO::FETCH_ASSOC);

		$result = $data[0];
		$result["task"] = json_decode($data[0]["json"], true);
		
		if (isset($result["task"]["delegate"])) {
			$user = $this->registry["user"]->getUserInfo($result["task"]["delegate"]);
			$result["task"]["delegate_user"] = '<span style="font-size: 11px; margin-right: 10px;">' . $user["name"] . ' ' . $user["soname"] . '</span>';
			$result["task"]["delegate_user"] .= '<input type="hidden" name="delegate" value="' . $result["task"]["delegate"] . '" />';
		}
		
		return $result;
	}
	
	function delDraftRouteTask($tid) {
		$sql = "DELETE FROM draft_route_tasks WHERE id = :tid LIMIT 1";
		
		$res = $this->registry['db']->prepare($sql);
		$param = array(":tid" => $tid);
		$res->execute($param);
		
		$sql = "DELETE FROM draft_route_route_tasks WHERE tid = :tid LIMIT 1";
		
		$res = $this->registry['db']->prepare($sql);
		$param = array(":tid" => $tid);
		$res->execute($param);
	}
	
	
	function addDraftRouteAction($step_id, $ifdata, $ifcon, $ifval, $goto, $ifid) {
		end($ifid);
		$max = key($ifid);
		for($i=0; $i<=$max; $i++) {
			if ((isset($ifid[$i])) and (!isset($ifdata[$i]))) {
				$sql = "DELETE FROM draft_route_action WHERE id = :id LIMIT 1";
					
				$res = $this->registry['db']->prepare($sql);
				$param = array(":id" => $ifid[$i]);
				$res->execute($param);
			}
		}
		
		foreach($ifdata as $key=>$val) {
			if (isset($ifid[$key])) {
				$sql = "UPDATE draft_route_action SET step_id = :step_id, ifdata = :ifdata, ifcon = :ifcon, ifval = :ifval, goto = :goto WHERE id = :ifid";
					
				$res = $this->registry['db']->prepare($sql);
				$param = array(":step_id" => $step_id, ":ifdata" => $ifdata[$key], ":ifcon" => $ifcon[$key], ":ifval" => $ifval[$key], ":goto" => $goto[$key], ":ifid" => $ifid[$key]);
				$res->execute($param);
			} else {
				$sql = "INSERT INTO draft_route_action (step_id, ifdata, ifcon, ifval, goto) VALUES (:step_id, :ifdata, :ifcon, :ifval, :goto)";
					
				$res = $this->registry['db']->prepare($sql);
				$param = array(":step_id" => $step_id, ":ifdata" => $ifdata[$key], ":ifcon" => $ifcon[$key], ":ifval" => $ifval[$key], ":goto" => $goto[$key]);
				$res->execute($param);
			}
		}
	}
	
	function getDraftRouteAction($step_id) {
		$sql = "SELECT dra.id, dra.ifdata, dra.ifcon, dra.ifval, dra.goto, drs.name AS gotoval, drtr.name AS ifdataval
		FROM draft_route_action AS dra
		LEFT JOIN draft_route_step AS drs ON (drs.id = dra.goto)
		LEFT JOIN draft_route_tasks_results AS drtr ON (drtr.id = dra.ifdata)
		WHERE dra.step_id = :step_id";
		
		$res = $this->registry['db']->prepare($sql);
		$param = array(":step_id" => $step_id);
		$res->execute($param);
		$data = $res->fetchAll(PDO::FETCH_ASSOC);
		
		return $data;
	}
	
	function delDraftRouteAction() {
	
	}
	//End Draft

	function getRoutes() {
		$sql = "SELECT id, name FROM route ORDER BY id DESC";
		
		$res = $this->registry['db']->prepare($sql);
		$param = array();
		$res->execute($param);
		$rows = $res->fetchAll(PDO::FETCH_ASSOC);

		return $rows;
	}
	
	function addRealRoutes($rid) {
		$step_id = array(); $result_id = array();
		
		$sql = "SELECT name FROM draft_route WHERE id = :rid LIMIT 1";
		
		$res = $this->registry['db']->prepare($sql);
		$param = array(":rid" => $rid);
		$res->execute($param);
		$data = $res->fetchAll(PDO::FETCH_ASSOC);
		
		$sql = "INSERT INTO route (name) VALUES (:name)";
		
		$res = $this->registry['db']->prepare($sql);
		$param = array(":name" => $data[0]["name"]);
		$res->execute($param);
		
		$new_rid = $this->registry['db']->lastInsertId();
		
		$sql = "SELECT tid, step_id FROM draft_route_route_tasks WHERE rid = :rid";
		
		$res = $this->registry['db']->prepare($sql);
		$param = array(":rid" => $rid);
		$res->execute($param);
		$data = $res->fetchAll(PDO::FETCH_ASSOC);
		
		foreach($data as $part) {
			$sql = "SELECT `order`, name FROM draft_route_step WHERE id = :step_id LIMIT 1";
			
			$res = $this->registry['db']->prepare($sql);
			$param = array(":step_id" => $part["step_id"]);
			$res->execute($param);
			$row = $res->fetchAll(PDO::FETCH_ASSOC);
			
			if (!isset($step_id[$part["step_id"]])) {
				$sql = "INSERT INTO route_step (`order`, name) VALUES (:order, :name)";
					
				$res = $this->registry['db']->prepare($sql);
				$param = array(":order" => $row[0]["order"], ":name" => $row[0]["name"]);
				$res->execute($param);
				
				$step_id[$part["step_id"]] = $this->registry['db']->lastInsertId();
			}

			$sql = "SELECT uid, json FROM draft_route_tasks WHERE id = :tid LIMIT 1";
			
			$res = $this->registry['db']->prepare($sql);
			$param = array(":tid" => $part["tid"]);
			$res->execute($param);
			$row = $res->fetchAll(PDO::FETCH_ASSOC);

			$task = $row[0]["json"];
			
			$sql = "INSERT INTO route_tasks (uid, json) VALUES (:uid, :json)";
			
			$res = $this->registry['db']->prepare($sql);
			$param = array(":uid" => $row[0]["uid"], ":json" => $task);
			$res->execute($param);
			
			$new_tid = $this->registry['db']->lastInsertId();
			
			$sql = "INSERT INTO route_route_tasks (rid, tid, step_id) VALUES (:rid, :tid, :step_id)";
			
			$res = $this->registry['db']->prepare($sql);
			$param = array(":rid" => $new_rid, ":tid" => $new_tid, ":step_id" => $step_id[$part["step_id"]]);
			$res->execute($param);
			
			$sql = "SELECT id, name, type, datatype FROM draft_route_tasks_results WHERE tid = :tid";
				
			$res = $this->registry['db']->prepare($sql);
			$param = array(":tid" => $part["tid"]);
			$res->execute($param);
			$row = $res->fetchAll(PDO::FETCH_ASSOC);
			
			foreach($row as $val) {
				$sql = "INSERT INTO route_tasks_results (tid, name, type, datatype) VALUES (:tid, :name, :type, :datatype)";
					
				$res = $this->registry['db']->prepare($sql);
				$param = array(":tid" => $new_tid, ":name" =>  $val["name"], ":type" =>  $val["type"], ":datatype" =>  $val["datatype"]);
				$res->execute($param);
				
				$result_id[$val["id"]] = $this->registry['db']->lastInsertId();
			}
			
			foreach($result_id as $key=>$val) {
				$task = str_replace("$[" . $key . "]", "$[" . $val . "]", $task);
			}
			
			$sql = "UPDATE route_tasks SET json = :json WHERE id = :id";
				
			$res = $this->registry['db']->prepare($sql);
			$param = array(":id" => $new_tid, ":json" => $task);
			$res->execute($param);
		}

		foreach($step_id as $key=>$val) {
			$sql = "SELECT ifdata, ifcon, ifval, goto FROM draft_route_action WHERE step_id = :step_id";
			
			$res = $this->registry['db']->prepare($sql);
			$param = array(":step_id" => $key);
			$res->execute($param);
			$row = $res->fetchAll(PDO::FETCH_ASSOC);
				
			foreach($row as $val) {
				$sql = "INSERT INTO route_action (step_id, ifdata, ifcon, ifval, goto) VALUES (:step_id, :ifdata, :ifcon, :ifval, :goto)";
			
				$res = $this->registry['db']->prepare($sql);
				$param = array(":step_id" => $step_id[$key], ":ifdata" =>  $result_id[$val["ifdata"]], ":ifcon" => $val["ifcon"], ":ifval" =>  $val["ifval"], ":goto" =>  $step_id[$val["goto"]]);
				$res->execute($param);
			}
		}
	}
}
?>
