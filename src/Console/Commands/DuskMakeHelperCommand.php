<?php

namespace App\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

class DuskMakeHelperCommand extends GeneratorCommand {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $signature = 'quilo-dusk:helper {name : The name of the class}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new Dusk Helper test class';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Helper';

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	protected function getStub() {
		return $this->laravel->basePath() . '/tests/Browser/Stubs/testhelper.stub';

	}

	/**
	 * Get the destination class path.
	 *
	 * @param  string  $name
	 * @return string
	 */
	protected function getPath($name) {
		$name = Str::replaceFirst($this->rootNamespace(), '', $name);

		return $this->laravel->basePath() . '/tests' . str_replace('\\', '/', $name) . '.php';
	}

	/**
	 * Get the default namespace for the class.
	 *
	 * @param  string  $rootNamespace
	 * @return string
	 */
	protected function getDefaultNamespace($rootNamespace) {
		return $rootNamespace . '\Browser\Helpers';
	}

	/**
	 * Get the root namespace for the class.
	 *
	 * @return string
	 */
	protected function rootNamespace() {
		return 'Tests';
	}
}
