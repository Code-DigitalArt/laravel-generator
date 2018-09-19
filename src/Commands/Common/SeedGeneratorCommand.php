<?php

namespace InfyOm\Generator\Commands\Common;

use InfyOm\Generator\Commands\BaseCommand;
use InfyOm\Generator\Common\CommandData;
use InfyOm\Generator\Generators\SeedGenerator;

class SeedGeneratorCommand extends BaseCommand
{
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'infoym.seed';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create seed command';

	/**
	 * Create a new command instance.
	 */
	public function __construct()
	{
		parent::__construct();

		$this->commandData = new CommandData($this, CommandData::$COMMAND_TYPE_API);
	}

	/**
	 * Execute the command.
	 *
	 * @return void
	 */
	public function handle()
	{
		parent::handle();

		$seedGenerator = new SeedGenerator($this->commandData);
		$seedGenerator->generate();

		$this->performPostActions();
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	public function getOptions()
	{
		return array_merge(parent::getOptions(), []);
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array_merge(parent::getArguments(), []);
	}
}
