<?php

namespace InfyOm\Generator\Generators\Scaffold;

use InfyOm\Generator\Common\CommandData;
use InfyOm\Generator\Generators\BaseGenerator;
use InfyOm\Generator\Utils\FileUtil;
use InfyOm\Generator\Utils\GeneratorFieldsInputUtil;

class ControllerGenerator extends BaseGenerator
{
    /** @var CommandData */
    private $commandData;

    /** @var string */
    private $path;

    /** @var string */
    private $templateType;

    /** @var string */
    private $fileName;

    public function __construct(CommandData $commandData)
    {
        $this->commandData = $commandData;
        $this->path = $commandData->config->pathController;
        $this->templateType = config('infyom.laravel_generator.templates', 'adminlte-templates');
        $this->fileName = $this->commandData->modelName.'Controller.php';
    }

    public function generate()
    {
        if ($this->commandData->getAddOn('datatables')) {
            $templateData = get_template('scaffold.controller.datatable_controller', 'laravel-generator');

            $this->generateDataTable();
        } else {
            $templateData = get_template('scaffold.controller.controller', 'laravel-generator');

            $paginate = $this->commandData->getOption('paginate');

            if ($paginate) {
                $templateData = str_replace('$RENDER_TYPE$', 'paginate('.$paginate.')', $templateData);
            } else {
                $templateData = str_replace('$RENDER_TYPE$', 'all()', $templateData);
            }
        }

        $templateData = fill_template($this->commandData->dynamicVars, $templateData);


		$modelName = $this->commandData->dynamicVars["\$MODEL_NAME_CAMEL$"];
        $relations = $this->commandData->relations;
        $store_relations = [];
        $manyToManyModelRepositories = [];
        $manyToOneModelRepositories = [];
        $modelRepoAttributes = [];
        $varModelRepos = [];
        $constructModelRepos = [];
        $getModelRepos = [];
        $sendModelRepos = [];

	    $fieldsFile = $this->commandData->config->options["fieldsFile"];
	    $fieldsFileLocation = substr($fieldsFile, 0, strrpos($fieldsFile, "/") +1);



	    foreach ($relations as $relation)
	    {
		    if(!$relation->inputs[0] == ''){
			    $cc_relation = camel_case($relation->inputs[0]);

			    $relationfieldsFile = $fieldsFileLocation . $relation->inputs[0] . '.json';
			    $relatedFields      = $this->getDataFromFieldsFile($relationfieldsFile);

			    if($relation->type == 'mt1'){
					$manyToManyModelRepositories[] = str_replace('Relation', $relation->inputs[0],'use App\Repositories\RelationRepository;');
				    $modelRepoAttributes[] = str_replace('relation', $cc_relation, 'private $relationRepository;');
				    $varModelRepos[] = str_replace('Relation', $cc_relation, ', RelationRepository ') . str_replace('relation', $cc_relation, '$relationRepo');
				    $constructModelRepos[] = str_replace('relation', $cc_relation, '$this->relationRepository = $relationRepo;');
				    $getModelRepos[] = str_replace( 'relation', $cc_relation, '$'.$cc_relation. 's = $this->relationRepository->all();');
				    $sendModelRepos[] = str_replace('relation', $cc_relation, "->with('relations', \$relations)");
			    }

			    foreach ($relatedFields as $field){
			    	if(!empty($field->htmlValues[0])){
			    		if(strstr($field->htmlValues[0], 'foreign')){
			    			$foreignArray = explode(':', $field->htmlValues[0]);
						    $manyToManyModelRepositories[] = str_replace('Relation', $foreignArray[1],'use App\Repositories\RelationRepository;');
						    $modelRepoAttributes[] = str_replace('relation', camel_case($foreignArray[1]), 'private $relationRepository;');
						    $varModelRepos[] = str_replace('Relation', $foreignArray[1], ', RelationRepository ') . str_replace('relation', camel_case($foreignArray[1]), '$relationRepo');
						    $constructModelRepos[] = str_replace('relation', camel_case($foreignArray[1]), '$this->relationRepository = $relationRepo;');
						    $getModelRepos[] = str_replace( 'relation', camel_case($foreignArray[1]), '$'.camel_case($foreignArray[1]). 's = $this->relationRepository->all();');
						    $sendModelRepos[] = str_replace('relation', camel_case($foreignArray[1]), "->with('relations', \$relations)");
					    }
				    }
			    }


			    if(empty($relation->inputs[1])){
				    $store_relations[] = str_replace('relation', $cc_relation, 'if(!$request->input(\'relation\') == null){'.'$'.$modelName."->relations()->createMany(\$request->input('relation'));}");
			    }else {
				    $store_relations[] = str_replace('relation', $cc_relation, 'if(!$request->input(\'relations\') == null){'.'$'.$modelName."->relations()->sync(\$request->input('relations'));}");
			    }
			    if(!empty($relation->inputs[1])){
				    $manyToManyModelRepositories[] = str_replace('Relation', $relation->inputs[0],'use App\Repositories\RelationRepository;');
				    $modelRepoAttributes[] = str_replace('relation', $cc_relation, 'private $relationRepository;');
				    $varModelRepos[] = str_replace('Relation', $relation->inputs[0], ', RelationRepository ') . str_replace('relation', $cc_relation, '$relationRepo');
				    $constructModelRepos[] = str_replace('relation', $cc_relation, '$this->relationRepository = $relationRepo;');
				    $getModelRepos[] = str_replace( 'relation', $cc_relation, '$'.$cc_relation. 's = $this->relationRepository->all();');
				    $sendModelRepos[] = str_replace('relation', $cc_relation, "->with('relations', \$relations)");
			    }

		    }
	    }

	    $templateData = str_replace('$MANY_TO_MANY_MODEL_REPOSITORIES$', implode("\n", $manyToManyModelRepositories), $templateData);
	    $templateData = str_replace('$MODEL_REPO_ATTRIBUTES$', implode("\n", $modelRepoAttributes), $templateData);
	    $templateData = str_replace('$VAR_MODEL_REPOS$', implode('', $varModelRepos), $templateData);
	    $templateData = str_replace('$CONSTRUCT_MODEL_REPOS$', implode("\n", $constructModelRepos), $templateData);
	    $templateData = str_replace('$GET_MODEL_REPOS$', implode("\n", $getModelRepos), $templateData);
	    $templateData = str_replace('$SEND_MODEL_REPOS$', implode('', $sendModelRepos), $templateData);
	    $templateData = str_replace('$STORE_RELATIONS$', implode("\n\n", $store_relations), $templateData);


        FileUtil::createFile($this->path, $this->fileName, $templateData);

        $this->commandData->commandComment("\nController created: ");
        $this->commandData->commandInfo($this->fileName);
    }

    private function generateDataTable()
    {
        $templateData = get_template('scaffold.datatable', 'laravel-generator');

        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        $headerFieldTemplate = get_template('scaffold.views.datatable_column', $this->templateType);

        $headerFields = [];

        foreach ($this->commandData->fields as $field) {
            if (!$field->inIndex) {
                continue;
            }
            $headerFields[] = $fieldTemplate = fill_template_with_field_data(
                $this->commandData->dynamicVars,
                $this->commandData->fieldNamesMapping,
                $headerFieldTemplate,
                $field
            );
        }

        $path = $this->commandData->config->pathDataTables;

        $fileName = $this->commandData->modelName.'DataTable.php';

        $fields = implode(','.infy_nl_tab(1, 3), $headerFields);

        $templateData = str_replace('$DATATABLE_COLUMNS$', $fields, $templateData);

        FileUtil::createFile($path, $fileName, $templateData);

        $this->commandData->commandComment("\nDataTable created: ");
        $this->commandData->commandInfo($fileName);
    }

    public function rollback()
    {
        if ($this->rollbackFile($this->path, $this->fileName)) {
            $this->commandData->commandComment('Controller file deleted: '.$this->fileName);
        }

        if ($this->commandData->getAddOn('datatables')) {
            if ($this->rollbackFile($this->commandData->config->pathDataTables, $this->commandData->modelName.'DataTable.php')) {
                $this->commandData->commandComment('DataTable file deleted: '.$this->fileName);
            }
        }
    }
}
