<?php

class Credits {

    private $db;

    public function __construct() {
        $this->db = new DB();
    }

    /**
     * Add credit to a user and log the transaction.
     *
     * @param int $uid User ID
     * @param float $amount Amount to be added
     * @param array $options
     * @return bool
     */
    public function add_credit($uid, $amount, $options = []) {

        if ($amount <= 0) return false;

        $this->log_action([
            'uid' => $uid,
            'amount' => $amount,
            'type' => $options['type'] ?? 'deposit',
            'credit_way' => 'plus',
            'slip_id' => $options['slip_id'] ?? NULL,
            'method' => $options['method'] ?? 'cash',
            'note' => $options['note'] ?? NULL,
            'status' => 'completed',
            'created_at' => time(),
        ]);

        return $this->update_balance($uid, $amount);
    }

    /**
     * Subtract credit from a user and log the transaction.
     *
     * @param int $uid User ID
     * @param float $amount Amount to be subtracted
     * @param array $options
     * @return bool
     */
    public function subtract_credit($uid, $amount, $options = []) {
        if ($amount <= 0) return false;

        $balance = $this->get_user_balance($uid);
        if ($balance < $amount) return false;

        $this->log_action([
            'uid'        => $uid,
            'amount'     => -1 * $amount,
            'type' => $options['type'] ?? 'deposit',
            'credit_way' => 'minus',
            'slip_id' => $options['slip_id'] ?? NULL,
            'method' => $options['method'] ?? 'cash',
            'note' => $options['note'] ?? NULL,
            'status'     => 'completed',
            'created_at' => time(),
        ]);

        return $this->update_balance($uid, -1 * $amount);
    }

    /**
     * Withdraw credit from a user and log the transaction.
     *
     * @param int $uid User ID
     * @param float $amount Amount to be subtracted
     * @param array $options
     * @return bool
     */
    public function withdraw($uid, $amount, $options = []) {
        if ($amount <= 0) return false;

        $balance = $this->get_user_balance($uid);
        if ($balance < $amount) return false;

        $this->log_action([
            'uid'        => $uid,
            'amount'     => -1 * $amount,
            'type' => 'withdrawal',
            'credit_way' => 'minus',
            'method' => $options['method'] ?? 'cash',
            'note' => $options['note'] ?? NULL,
            'status'     => 'completed',
            'created_at' => time(),
        ]);

        return $this->update_balance($uid, -1 * $amount);
    }

    /**
     * Perform a cashout operation for the user.
     *
     * @param int $uid User ID
     * @param float $amount Amount to cash out
     * @param string $method Method (e.g., bank, crypto)
     * @return bool
     */
    public function cashout($uid, $amount, $method = 'manual') {
        return $this->subtract_credit($uid, $amount, 'cashout', $method);
    }

    /**
     * Get the current balance of a user.
     *
     * @param int $uid User ID
     * @return float
     */
    public function get_user_balance($uid) {
        $user = $this->db->from('z_users')->where('uid', $uid)->first();
        return $user['balance'] ?? 0;
    }

    /**
     * Update user balance.
     *
     * @param int $uid User ID
     * @param float $delta Amount to add or subtract
     * @return bool
     */
    private function update_balance($uid, $delta) {
        $current = $this->get_user_balance($uid);
        $new_balance = $current + $delta;

        $this->db->update('z_users')
            ->where('uid', $uid)
            ->set([
                'balance' => $new_balance
            ]);
        return true;
    }

    /**
     * Log a credit transaction to the database.
     *
     * @param array $data Transaction data
     * @return void
     */
    private function log_action($data) {
        $data['created_at'] = $data['created_at'] ?? time();
        $data['status']     = $data['status'] ?? 'completed';
        $this->db->insert('credit_actions')->set($data);
    }

    public function credit_action_validate($values, $form) {
        if($values['type'] == 'subtract' && (int)$values['amount'] > $values['user']['balance']){
    
            \JS::live('message', [
                'message_type' => 'form',
                'type' => 'error',
                'message' => t('Substract_amount_can_not_be_lower_than_user_balance'),
            ]);

            return FALSE;
        }

    }

    public function credit_action_submit($values, $form) {

        $method = $values['type'].'_credit';
        $this->$method($values['user']['uid'],$values['amount'], [
            'note' => $values['note'],
        ]);

        JS::st1(PANEL_PATH.'/user/'.$values['user']['uid'].'/credit-activities', 'Credit action is saved');

    }

    public function withdraw_validate($values, $form) {
        if((int)$values['amount'] > $values['user']['balance']){
    
            \JS::live('message', [
                'message_type' => 'form',
                'type' => 'error',
                'message' => t('Withdrawal_amount_can_not_be_lower_than_user_balance'),
            ]);

            return FALSE;
        }

    }

    public function withdraw_submit($values, $form) {

        $this->withdraw($values['user']['uid'],$values['amount'], [
            'note' => $values['note'],
        ]);

        JS::st1(PANEL_PATH.'/user/'.$values['user']['uid'].'/credit-activities', 'Withdraw is saved');

    }
}
