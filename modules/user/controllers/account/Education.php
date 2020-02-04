<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Add or update user education
 *
 * @author		Aby Dahana
 * @profile		abydahana.github.io
 */
class Education extends Aksara
{
	private $_table									= 'users__educations';
	public function __construct()
	{
		parent::__construct();
		
		$this->set_permission();
		
		$this->permission->must_ajax();
		$this->load->library('form_validation');
		$this->load->helper('security');
		
		$this->_primary								= $this->input->get('id');
	}
	
	public function index()
	{
		/* validate the type of contact */
		$this->form_validation->set_rules('type', phrase('education_type'), 'required|in_list[1,2,3]');
		
		/* validate input */
		$this->form_validation->set_rules('value', phrase('education_name'), 'required|xss_clean|callback_validate_input[users__educations]');
		$this->form_validation->set_rules('start', phrase('start_date'), 'required|valid_date');
		if(!$this->input->post('present'))
		{
			$this->form_validation->set_rules('end', phrase('end_date'), 'required|valid_date');
		}
		$this->form_validation->set_rules('present', phrase('present'), 'is_boolean');
		$this->form_validation->set_rules('description', phrase('present'), 'xss_clean');
		
		/* input isn't valid */
		if($this->form_validation->run() === false)
		{
			/* throw exception */
			return throw_exception(400, $this->form_validation->error_array());
		}
		
		/* prepare data */
		$prepare									= array
		(
			'user_id'								=> get_userdata('user_id'),
			'type'									=> $this->input->post('type'),
			'value'									=> $this->input->post('value'),
			'description'							=> $this->input->post('description'),
			'start'									=> $this->input->post('start'),
			'end'									=> $this->input->post('end'),
			'present'								=> $this->input->post('present')
		);
		
		/* check if it being update or insert */
		$query										= $this->model->get_where
		(
			$this->_table,
			array
			(
				'user_id'							=> get_userdata('user_id'),
				'id'								=> $this->_primary,
				'type'								=> $this->input->post('type')
			),
			1
		)
		->row();
		
		if($query)
		{
			/* update existing */
			$execute								= $this->model->update($this->_table, $prepare, array('user_id' => get_userdata('user_id'), 'id' => $this->_primary), 1);
			$id										= $this->_primary;
		}
		else
		{
			/* insert new data */
			$execute								= $this->model->insert($this->_table, $prepare);
			$id										= $this->model->insert_id();
		}
		
		if($execute)
		{
			return make_json
			(
				array
				(
					'status'						=> 200,
					'template'						=> 'education',
					'override'						=> ($this->_primary ? '#education_' . $this->_primary : null),
					'action_href'					=> base_url('user/account/education', array('skills', 'id' => $id)),
					'delete_href'					=> base_url('user/account/education', array('id' => $id, 'delete' => true)),
					'type'							=> $this->input->post('type'),
					'value'							=> $this->input->post('value'),
					'description'					=> $this->input->post('description'),
					'start'							=> $this->input->post('start'),
					'end'							=> $this->input->post('end'),
					'present'						=> $this->input->post('present')
				)
			);
		}
		
		/* there's something wrong that data cannot be executed */
		return throw_exception(500, phrase('unable_to_submit_your_data') . ' ' . phrase('please_try_again_or_contact_the_system_administrator'));
	}
	
	/**
	 * Validate input
	 */
	public function validate_input($value = null, $type = 0)
	{
		if($this->_primary)
		{
			$checker								= $this->model->get_where
			(
				$this->_table,
				array
				(
					'id != '						=> $this->_primary,
					'value'							=> $value,
					'user_id'						=> get_userdata('user_id'),
					'type'							=> $type
				),
				1
			)
			->row();
		}
		else
		{
			$checker								= $this->model->get_where
			(
				$this->_table,
				array
				(
					'value'							=> $value,
					'user_id'						=> get_userdata('user_id'),
					'type'							=> $type
				),
				1
			)
			->row();
		}
		
		$max										= $this->model->where
		(
			array
			(
				'id != '							=> $this->_primary,
				'user_id'							=> get_userdata('user_id'),
				'type'								=> $type
			)
		)
		->count_all_results($this->_table);
		
		if($checker)
		{
			$this->form_validation->set_message('validate_input', phrase('the_education_you_would_to_add_is_already_exists'));
			return false;
		}
		elseif($max >= 15)
		{
			$this->form_validation->set_message('validate_input', phrase('unfortunately_you_are_reached_the_maximum_quota_of_education_storage'));
			return false;
		}
		
		return true;
	}
}