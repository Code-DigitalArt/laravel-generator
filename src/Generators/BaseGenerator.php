<?php

namespace InfyOm\Generator\Generators;

use InfyOm\Generator\Common\GeneratorField;
use InfyOm\Generator\Common\GeneratorFieldRelation;
use InfyOm\Generator\Utils\FileUtil;
use Complex\Exception;

class BaseGenerator
{
	public function getDataFromFieldsFile($fieldsFile)
	{
		try {
			if (file_exists($fieldsFile)) {
				$filePath = $fieldsFile;
			} elseif (file_exists(base_path($fieldsFile))) {
				$filePath = base_path($fieldsFile);
			} else {
				$schemaFileDirector = config('infyom.laravel_generator.path.schema_files');
				$filePath = $schemaFileDirector.$fieldsFile;
			}

			if (!file_exists($filePath)) {
				echo 'Fields file not found';
				exit;
			}

			$fileContents = file_get_contents($filePath);
			$jsonData = json_decode($fileContents, true);
			$relatedFields = [];

			$this->fields = [];
			foreach ($jsonData as $field) {
				if (isset($field['type']) && $field['relation']) {
					$relation = GeneratorFieldRelation::parseRelation($field['relation']);
				} else {
					$relatedFields[] = GeneratorField::parseFieldFromFile($field);
					if (isset($field['relation'])) {
						$relation = GeneratorFieldRelation::parseRelation($field['relation']);
					}
				}
			}


		} catch (Exception $e) {
			$e->getMessage();
			exit;
		}

		return $relatedFields;
	}

    public function rollbackFile($path, $fileName)
    {
        if (file_exists($path.$fileName)) {
            return FileUtil::deleteFile($path, $fileName);
        }

        return false;
    }
}
