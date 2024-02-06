<?php

App::uses('DboSource', 'Model/Datasource');

#[\AllowDynamicProperties]
class DboDummy extends DboSource {

	public function connect() {
		return true;
	}

}
