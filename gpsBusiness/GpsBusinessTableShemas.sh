Gps_expenses
	id 	expense_name 	expense_type 	amount 	expense_date 	comment 	created_at 	updated_at 	

gps_payments
 id 	payment_number 	gps_order_id 	payment_date 	payment_type 	paid_by 	paid_amount 	currency 	rate 	bank 	deposited_to 	image_location 	created_at 	


gps_payment_followup
id 	payment_date 	paid_by 	currency 	amount 	rate 	transfer_to 	bank_platform_name 	comment 	payment_image 	created_at 	updated_at 	


gps_profit
	id 	profit_date 	total_purchase 	total_sales 	total_credit 	total_profit 	total_expenses 	comment 	created_at 	updated_at 	


gps_sales
id 	buyer_name 	contact 	sales_type 	quantity 	unit_price 	total 	currency 	rate 	sale_date 	created_at 	


gps_orders
id 	order_number 	order_date 	ordered_amount 	unit_price 	quantity 	total_price 	has_credit 	credit_amount 	created_at

gps_investors
id 	name 	share_percentage 	investment 	initial_investment_date 	initial_investment_amount_etb 	initial_investment_amount_usd 	reinvested_profits_etb 	current_share_value_etb 	total_withdrawals_etb 	account_type 	balance 	credit_amount 	created_at 	updated_at 	
	
gps_business_cycle_orders
id 	business_cycle_id 	order_id 	created_at 	

gps_business_cycle_sales
	id 	business_cycle_id 	sale_id 	created_at 	

gps_business_cycles
id 	cycle_number 	start_date 	end_date 	status 	total_purchase_etb 	total_purchase_usd 	total_sales_etb 	total_sales_usd 	total_expenses_etb 	total_credit_amount 	gross_profit_etb 	net_profit_etb 	notes 	created_at 	updated_at 	


gps_business_expenses
id 	business_cycle_id 	expense_date 	description 	amount_etb 	expense_type 	payment_method 	reference_number 	notes 	created_at 	updated_at 	created_by 	

gps_business_expense_history
	id 	expense_id 	action 	old_value 	new_value 	notes 	created_at 	created_by 	

gps_credit_tracking
id 	order_id 	business_cycle_id 	credit_amount_etb 	remaining_balance_etb 	expected_payment_date 	status 	notes 	created_at 	updated_at 	

gps_currency_rates
id 	date 	usd_to_etb_rate 	etb_to_usd_rate 	source 	bank_name 	notes 	created_at 	

gps_customers
id 	name 	email 	phone 	address 	created_at 	updated_at 	

gps_expense_categories
 id 	name 	expense_type 	description 	unit_price 	created_at 	

 gps_profit_distributions
  	id 	business_cycle_id 	investor_id 	distribution_date 	amount_etb 	distribution_type 	status 	reinvested 	notes 	created_at 	updated_at 	

gps_balance_accounts
id 	account_type 	reference_id 	transaction_type 	transaction_id 	currency 	rate 	amount 	balance 	description 	created_at 	updated_at 	