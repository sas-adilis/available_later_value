<?php

class AdminAvailableLaterValueController extends ModuleAdminController
{

	public function __construct() {
	    $this->table = 'available_later_value';
	    $this->name = 'available_later_value';
	    $this->className = 'AvailableLaterValue';
	    $this->identifier = 'id_available_later_value';
	    $this->lang = true;
	    $this->bootstrap = true;
	    $this->actions = array('edit','duplicate','delete');

        parent::__construct();

	    $this->fields_list = [
	    	'id_available_later_value' => [
	    	    'title' => $this->l('ID'),
	    	    'type' => 'int',
	    	    'width' => '30',
	    	    'align' => 'center'
	    	],
            'reference' => [
                'title' => $this->l('Référence'),
            ],
            'name' => [
                'title' => $this->l('Délai'),
            ],
			'description_short' => [
			    'title' => $this->l('Description courte')
			],
			'delay_in_days' => [
			    'title' => $this->l('Délai en jours'),
			    'type' => 'int',
				'width' => '100',
			    'align' => 'center'
			],

        ];
	
	    $this->fields_form = [
            'legend' => [
                'title' => $this->l('Parameters'),
                'icon' => 'icon-cogs'
            ],
            'input' => [
                [
                    'type' => 'text',
                    'name' => 'reference',
                    'id' => 'reference',
                    'label' => $this->l('Référence'),
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'name' => 'name',
                    'id' => 'name',
                    'label' => $this->l('Valeur'),
                    'required' => true,
                    'lang' => true,
                ],
                [
                    'type' => 'text',
                    'name' => 'description_short',
                    'id' => 'description_short',
                    'label' => $this->l('Description courte'),
                    'required' => false,
                    'lang' => true,
                ], [

                    'type' => 'text',
                    'name' => 'description',
                    'id' => 'description',
                    'label' => $this->l('Description'),
                    'required' => false,
                    'lang' => true,
                ],
                [
                    'type' => 'text',
                    'name' => 'delay_in_days',
                    'id' => 'delay_in_days',
                    'label' => $this->l('Délai en jours'),
                    'required' => true,
                    'maxlength' => 3
                ]
            ],
            'submit' => [
                'title' => $this->l('Save')
            ]
        ];

	
	}
	
	
}