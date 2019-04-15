<?php
	$recordAttributesData = '62dfea525f10ed236d6fbf2909f4e3de:pizvQDBu0MLHmC6tBUFHDh0WtEk0d2jB';
	if (isset($recordAttributesData)) {
		$explodedPassword = explode(':', $recordAttributesData, 2);
		if(isset($explodedPassword[1])) {
			$explodedPassword[1] = $explodedPassword[1];
		} else {
			$explodedPassword[1] = '';
		}
		if (strlen($explodedPassword[0]) == 32) {
			$recordAttributesData = implode(':', [$explodedPassword[0], $explodedPassword[0], '0']);
		} elseif (strlen($explodedPassword[0]) == 64) {
			$recordAttributesData = implode(':', [$explodedPassword[0], $explodedPassword[0], '1']);
		}
	}

	echo $recordAttributesData;
	echo '<br>' . strlen($explodedPassword[0]);
?>