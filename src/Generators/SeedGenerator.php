<?php
/**
 * Created by PhpStorm.
 * User: radosun
 * Date: 9/19/18
 * Time: 3:30 PM
 */

namespace InfyOm\Generator\Generators;

use InfyOm\Generator\Common\CommandData;
use InfyOm\Generator\Utils\FileUtil;

class SeedGenerator extends BaseGenerator
{
	/** @var CommandData */
	private $commandData;

	/** @var string */
	private $path;
	private $fileName;

	public function __construct($commandData)
	{
		$this->commandData = $commandData;
		$this->path = base_path('database/seeds/');
		$this->fileName = $this->commandData->modelName.'Seed.php';
	}

	public function generate()
	{
		$templateData = get_template('seed', 'laravel-generator');

		$templateData = fill_template($this->commandData->dynamicVars, $templateData);




		FileUtil::createFile($this->path, $this->fileName, $templateData);

		$this->commandData->commandComment("\nMigration created: ");
		$this->commandData->commandInfo($this->fileName);
	}

	public function rollback()
	{

	}
}