<?php
namespace li3_fixtures\extensions\command;

use li3_fixtures\test\Fixture;

use lithium\core\Libraries;

class Fixtures extends \lithium\console\Command {

	/**
	 *
	 * @return void
	 */
	public function run($method = null, $model = null) {
		if (!$method) {
			return $this->methods();
		}
	}

	public function save($model = null) {
		if (!$model) {
			$this->models();
		}
		$file = Fixture::file($this->model);
		if (file_exists($file)) {
			$prompt = "{$this->file($file)} already exists. Overwrite?";
			$choices = array('y', 'n');
			if ($this->in($prompt, compact('choices')) != 'y') {
				return $this->out("{$file} skipped.");
			}
		}
		$model = $this->model;
		$data = $model::find('all');
		if (Fixture::save($this->model, $data->data())) {
			$count = $data->count();
			$this->out("{$file} created.");
			return $this->out("Saved {$count} records.");
		}
		return $this->error("{$file} could not be created.");
	}

	public function load($model = null) {
		if (!$model) {
			$this->models();
		}
		$file = Fixture::file($this->model);
		if (!is_file($file)) {
			return $this->error("{$this->file($file)} not found.");
		}
		$model = $this->model;
		$prompt = "Truncate data before import?";
		$choices = array('y', 'n');
		if ($this->in($prompt, compact('choices')) == 'y') {
			$model::remove();
			$this->out("Truncated.");
		}
		$fixtures = Fixture::load($this->model);
		
		$save = function($item) use ($model){
			if(empty($item)) {
				return false;
			}
			$entity = $model::create();
			return $entity->save($item, array('validate' => false, 'callbacks' => false));
		};
		$result = $fixtures->map($save, array('collect' => false));
		$total = $fixtures->count();
		$count = count(array_filter($result));
		$this->out("Data from {$file} loaded.");
		return $this->out("Imported {$count} from {$total} records.");
	}

	protected function file($file) {
		return str_replace(LITHIUM_APP_PATH, '', $file);
	}

	protected function methods() {
		$header = 'Choose action';
		$choices = array('l', 's');
		$default = 's';
		$this->out($header, 'heading');
		$method = $this->in('Do you want to (l)oad or (s)ave data?', compact('choices', 'default'));
		switch ($method) {
			case 's':
				return $this->save();
			case 'l':
				return $this->load();
		}
	}

	protected function models() {
		$models = Libraries::locate('models');
		array_unshift($models, array());
		unset($models[0]);
		$header = 'List of Models, please choose one:';
		$columns = array(
			array('Id', 'Library', 'Model'),
			array('--', '-------', '-----')
		);
		$choices = array();
		foreach($models as $key => $item) {
			$row = $this->library_split($item);
			array_unshift($row, "{$key}.");
			$columns[] = $row;
		}

		$this->out($header, 'heading');
		$this->columns($columns);
		$model = $this->in('Which model?');
		$this->out($models[$model], 'heading');
		$this->model = $models[$model];
	}

	protected function library_split($model) {
		return list($library, $model) = explode('\\', str_replace('\\models', '', $model));
	}
}
