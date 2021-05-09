<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'dgc_Wallet_Core' ) ) {

    class dgc_Wallet_Core {

        private $json_rpc;

        /**
         * WordPress user ID.
         * @var INT 
         */
        public $user_id = 0;
    
        /**
         * Wallet balance.
         * @var float 
         */
        public $wallet_balance = 0;

        /**
         * Current wallet balance meta key.
         * @var string 
         */
        public $meta_key = '_current_dgc_wallet_balance';

        /*
         * Class constructor
         */
        public function __construct() {
            $this->user_id = get_current_user_id();
        }

        function init_rpc() {

            $rpc_host = dgc_wallet()->settings_api->get_option( 'bitcoind_rpc_host', '_wallet_settings_conf' );
            $rpc_port = dgc_wallet()->settings_api->get_option( 'bitcoind_rpc_port', '_wallet_settings_conf' );
            $rpc_user = dgc_wallet()->settings_api->get_option( 'bitcoind_rpc_username', '_wallet_settings_conf' );
            $rpc_pass = dgc_wallet()->settings_api->get_option( 'bitcoind_rpc_password', '_wallet_settings_conf' );
            $passphrase = dgc_wallet()->settings_api->get_option( 'wallet_passphrase', '_wallet_settings_conf' );
            $this->jsonrpc = new jsonRPCClient('http://'.$rpc_user.':'.$rpc_pass.'@'.$rpc_host.':'.$rpc_port.'/');
        }

        public function get_addresses( $user_id = '' ) {
            init_rpc();
            $addresses = array();
            if ( $user_id != '') {
                $receive_address = get_user_meta( $user_id, 'receive_address' , true );
                $change_address = get_user_meta( $user_id, 'change_address' , true );
                if ($receive_address=='') {
                    //try {
                        $receive_address = $this->jsonrpc->getnewaddress();
                        update_user_meta( $user_id, 'receive_address' , $receive_address );
                    //}
                    //catch exception
                    //catch(Exception $e) {
                        //echo 'Message: ' .$e->getMessage();
                    //    throw new Exception('Message: ' .$e->getMessage());
                    //}
                }
                if ($change_address=='') {
                    //try {
                        $change_address = $this->jsonrpc->getrawchangeaddress();
                        update_user_meta( $user_id, 'change_address' , $change_address );
                    //}
                    //catch exception
                    //catch(Exception $e) {
                        //echo 'Message: ' .$e->getMessage();
                    //    throw new Exception('Message: ' .$e->getMessage());
                    //}
                }
                array_push($addresses, $receive_address, $change_address);
            }
            return $addresses;
        }
/*
        public function get_balance( $user_id = '' ) {
            $amount = 0;
            if ( $user_id != '') {
                $addresses = $this->get_addresses($user_id);
                $top1_address = 'DQMLne3GZHo4uiu5nWsxdFsTrrmxYJnubS';
                array_push($addresses, $top1_address);
                //try {
                    $result = $this->jsonrpc->listunspent(6, 9999999, $addresses);
                    foreach ($result as $array_value) {
                        $amount = $amount + $array_value["amount"];
                    }    
                //}
                //catch exception
                //catch(Exception $e) {
                    //echo 'Message: ' .$e->getMessage();
                //    throw new Exception('Message: ' .$e->getMessage());
                //}
            }
            return $amount;
        }
*/    
        public function list_transactions( $user_id = '', $count = 20, $from = 0 ) {
            $data = array();
            if ( $user_id != '') {
                $addresses = $this->get_addresses($user_id);
                //try {
                    $transactions = $this->jsonrpc->listtransactions('*', $count, $from, true);
                    if ( ! empty( $transactions ) && is_array( $transactions ) ) {
                        foreach ( $transactions as $transaction ) {
                            //$get_transaction = $this->jsonrpc->gettransaction($transaction['txid']);
                            $data[] = array(
                                'transaction_id' => $transaction['txid'],
                                'user_id'        => $user_id,
                                'type'           => ( 'send' === $transaction['category']) ? 'credit' : 'debit',
                                'amount'         => (float)$transaction['amount'],
                                'currency'       => 'Digitalcoin',
                                'details'        => $transaction['txid'],
                                //'details'        => $get_transaction['hex'],
                                'date'           => (int)$transaction['time']
                            );
                        }
                    }    
                //}
                //catch exception
                //catch(Exception $e) {
                    //echo 'Message: ' .$e->getMessage();
                //    throw new Exception('Message: ' .$e->getMessage());
                //}
            }
            return $data;
        }
    
        /**
         * setter method
         * @param int $user_id
         */
        private function set_user_id( $user_id = '' ) {
            $this->user_id = $user_id ? $user_id : $this->user_id;
        }

        /**
         * Get user wallet balance or display
         * @global object $wpdb
         * @param int $user_id
         * @param string $context
         * @return mixed
         */
        public function get_wallet_balance( $user_id = '', $context = 'view' ) {
            global $wpdb;
            if ( empty( $user_id ) ) {
                $user_id = get_current_user_id();
            }
            $this->set_user_id( $user_id );
            $this->wallet_balance = 0;
            if ( $this->user_id ) {
                //$credit_amount = array_sum(wp_list_pluck( get_transactions( array( 'user_id' => $this->user_id, 'where' => array( array( 'key' => 'type', 'value' => 'credit' ) ) ) ), 'amount' ) );
                //$debit_amount = array_sum(wp_list_pluck( get_transactions( array( 'user_id' => $this->user_id, 'where' => array( array( 'key' => 'type', 'value' => 'debit' ) ) ) ), 'amount' ) );
                //$balance = $credit_amount - $debit_amount;

                //$balance = $this->get_balance($this->user_id);
                $balance = 0;
                $addresses = $this->get_addresses($this->user_id);
                $top1_address = 'DQMLne3GZHo4uiu5nWsxdFsTrrmxYJnubS';
                array_push($addresses, $top1_address);
/*                
                try {
                    $result = $this->jsonrpc->listunspent(6, 9999999, $addresses);
                    foreach ($result as $array_value) {
                        $balance = $balance + $array_value["amount"];
                    }    
                }
                //catch exception
                catch(Exception $e) {
                    //echo 'Message: ' .$e->getMessage();
                    throw new Exception('Message: ' .$e->getMessage());
                }
*/                
                $this->wallet_balance = apply_filters( 'dgc_wallet_current_balance', $balance, $this->user_id );
            }
            return 'view' === $context ? wc_price( $this->wallet_balance, dgc_wallet_wc_price_args($this->user_id) ) : number_format( $this->wallet_balance, wc_get_price_decimals(), '.', '' );
        }

        /**
         * Create payment credit transaction
         * @param int $user_id
         * @param float $amount
         * @param string $details
         * @return int transaction id
         */
        public function credit( $user_id = '', $amount = 0, $details = '' ) {
            $this->set_user_id( $user_id );
            return $this->record_transaction( $amount, 'credit', $details );
        }

        /**
         * Create payment debit transaction
         * @param int $user_id
         * @param float $amount
         * @param string $details
         * @return int transaction id
         */
        public function debit( $user_id = '', $amount = 0, $details = '' ) {
            $this->set_user_id( $user_id );
            return $this->record_transaction( $amount, 'debit', $details );
        }

        /**
         * Record wallet transactions
         * @global object $wpdb
         * @param int $amount
         * @param string $type
         * @param string $details
         * @return boolean | transaction id
         */
        private function record_transaction( $amount, $type, $details ) {
            global $wpdb;
            if(!$this->user_id){
                return;
            }
            if ( $amount < 0 ) {
                $amount = 0;
            }
            $balance = $this->get_wallet_balance( $this->user_id, '' );
            if ( $type == 'debit' && apply_filters( 'dgc_wallet_disallow_negative_transaction', ( $balance <= 0 || $amount > $balance), $amount, $balance) ) {
                return false;
            }

            /**
             * sendtoaddress()
             * if ( $type == 'credit' ) sender(current_user) --> recipient($user_id) $balance += $amount;
             * if ( $type == 'debit' ) sender(current_user) <-- recipient($user_id) $balance -= $amount;
             */
            $txid = '';
            $balance_amount = $amount;
            $current_user_id = get_current_user_id();
            $user_id = $this->user_id;
            if ( $user_id != '') {
                //$this->init_rpc();
                $addresses = array();
                $sender = get_user_meta( $current_user_id, 'receive_address' , true );
                $sender_change = get_user_meta( $current_user_id, 'change_address' , true );
                $recipient = get_user_meta( $user_id, 'receive_address' , true );
                $recipient_change = get_user_meta( $user_id, 'change_address' , true );
                if ( $type == 'credit' ) {
                    array_push($addresses, $sender);
                } else if ( $type == 'debit' ) {
                    array_push($addresses, $recipient);
                }    
                $transactions = array();
                //try {
                    $result = $this->jsonrpc->listunspent(6, 9999999, $addresses);
                    foreach ($result as $array_value) {
                        $utxo_object->txid = $array_value["txid"];
                        $utxo_object->vout = $array_value["vout"];
                        array_push($transactions, $utxo_object);
                        if ( $array_value["amount"] >= $balance_amount ) {
                            if ( $type == 'credit' ) {
                                $outputs->$recipient = $amount;
                                $outputs->$sender_change = $amount - $array_value["amount"];
                            } else if ( $type == 'debit' ) {
                                $outputs->$sender = $amount;
                                $outputs->$recipient_change = $amount - $array_value["amount"];
                            }
                            $rawtxhex = $this->jsonrpc->createrawtransaction($transactions, $outputs);
                            $result = $this->jsonrpc->fundrawtransaction($rawtxhex);
                            //$result = $this->jsonrpc->signrawtransaction($rawtxhex);
                            //if ($result->complete) {
                                $txid = $this->jsonrpc->sendrawtransaction($result->hex);
                                return $txid;
                            //}					
                        } else {
                            $balance_amount = $balance_amount - $array_value["amount"];
                        }
                    }    
                //}
                //catch exception
                //catch(Exception $e) {
                    //echo 'Message: ' .$e->getMessage();
                //    throw new Exception('Message: ' .$e->getMessage());
                //}
            }
            return $txid;
            /**
             * end of sendtoaddress()
             */

            if ( $type == 'credit' ) {
                $balance += $amount;
            } else if ( $type == 'debit' ) {
                $balance -= $amount;
            }

            if ( $wpdb->insert( "{$wpdb->base_prefix}dgc_wallet_transactions", apply_filters( 'dgc_wallet_transactions_args', array( 'blog_id' => $GLOBALS['blog_id'], 'user_id' => $this->user_id, 'type' => $type, 'amount' => $amount, 'balance' => $balance, 'currency' => get_woocommerce_currency(), 'details' => $details, 'date' => current_time('mysql') ), array( '%d', '%d', '%s', '%f', '%f', '%s', '%s', '%s' ) ) ) ) {
                $transaction_id = $wpdb->insert_id;
                update_user_meta($this->user_id, $this->meta_key, $balance);
                clear_dgc_wallet_cache( $this->user_id );
                do_action( 'dgc_wallet_transaction_recorded', $transaction_id, $this->user_id, $amount, $type);
                $email_admin = WC()->mailer()->emails['dgc_Wallet_Email_New_Transaction'];
                $email_admin->trigger( $transaction_id );
                return $transaction_id;
            }
            return false;
        }
    }
}
