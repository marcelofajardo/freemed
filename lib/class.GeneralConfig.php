<?php
	// $Id$
	// $Author$

class GeneralConfig {

	function GeneralConfig () {
		return true;
	} // end constructor GeneralConfig

	function init() {
		global $sql;

		$result = $sql->query("DROP TABLE config"); 
		$result = $sql->query($sql->create_table_query(
			'config',
			array (
				'c_option' => SQL__CHAR(64),
				'c_value' => SQL__VARCHAR(100),
				'id' => SQL__SERIAL
			), array ('id')
		));
		if ($result) $display_buffer .= "<li>".__("Configuration")."</li>\n";
		$stock_config = array (
			'icd9' => '9',
			'gfx' => '1',
			'calshr' => $cal_starting_hour,
			'calehr' => $cal_ending_hour,
			'cal_ob' => 'enable',
			'dtfmt' => 'ymd',
			'phofmt' => 'unformatted',
			'folded' => 'yes',
			'cal1' => '',
			'cal2' => '',
			'cal3' => '',
			'cal4' => '',
			'cal5' => '',
			'cal6' => '',
			'cal7' => '',
			'cal8' => '',
		);
		foreach ($stock_config AS $key => $val) {
			if (!is_integer($key)) {
				$result = $sql->query($sql->insert_query(
					'config',
					array (
						'c_option' => $key,
						'c_value' => $val
					)
				));
			}
		} // end looping through configure options

		return $result;
	} // end method init

} // end class GeneralConfig

?>
