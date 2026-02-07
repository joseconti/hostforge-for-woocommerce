<?php
/**
 * Namecheap Registrar Provider.
 *
 * Implements the HF_Registrar interface using the Namecheap XML API.
 *
 * @package HostForge\Modules\DomainManager\Registrars
 */

namespace HostForge\Modules\DomainManager\Registrars;

use HostForge\Abstracts\HF_API_Client;
use HostForge\Interfaces\HF_Registrar;
use HostForge\HF_Encryption;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Namecheap_Registrar
 */
class HF_Namecheap_Registrar extends HF_API_Client implements HF_Registrar {

	/**
	 * API user.
	 *
	 * @var string
	 */
	private string $api_user;

	/**
	 * API key.
	 *
	 * @var string
	 */
	private string $api_key;

	/**
	 * Namecheap username.
	 *
	 * @var string
	 */
	private string $username;

	/**
	 * Client IP for API whitelist.
	 *
	 * @var string
	 */
	private string $client_ip;

	/**
	 * Whether to use sandbox mode.
	 *
	 * @var bool
	 */
	private bool $sandbox;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->api_user  = get_option( 'hf_namecheap_api_user', '' );
		$this->api_key   = HF_Encryption::decrypt( get_option( 'hf_namecheap_api_key', '' ) );
		$nc_username     = get_option( 'hf_namecheap_username', '' );
		$this->username  = ! empty( $nc_username ) ? $nc_username : $this->api_user;
		$this->client_ip = get_option( 'hf_namecheap_client_ip', '' );
		$this->sandbox   = 'yes' === get_option( 'hf_namecheap_sandbox', 'no' );

		if ( $this->sandbox ) {
			$this->base_url = 'https://api.sandbox.namecheap.com/xml.response';
		} else {
			$this->base_url = 'https://api.namecheap.com/xml.response';
		}

		$this->timeout = 30;
	}

	/**
	 * Get the module ID for logging.
	 *
	 * @return string
	 */
	protected function get_id(): string {
		return 'domain-manager';
	}

	/**
	 * Check domain availability.
	 *
	 * @param string $domain Full domain name.
	 * @return array{available: bool, domain: string, premium: bool}
	 */
	public function check_availability( string $domain ): array {
		$result = $this->namecheap_api(
			'namecheap.domains.check',
			array( 'DomainList' => $domain )
		);

		if ( ! $result['success'] ) {
			return array(
				'available' => false,
				'domain'    => $domain,
				'premium'   => false,
			);
		}

		$xml = $result['data'];

		$available = false;
		$premium   = false;

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Namecheap API XML response property.
		if ( isset( $xml->CommandResponse->DomainCheckResult ) ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Namecheap API XML response property.
			$attrs     = $xml->CommandResponse->DomainCheckResult->attributes();
			$available = 'true' === (string) ( $attrs['Available'] ?? 'false' );
			$premium   = 'true' === (string) ( $attrs['IsPremiumName'] ?? 'false' );
		}

		return array(
			'available' => $available,
			'domain'    => $domain,
			'premium'   => $premium,
		);
	}

	/**
	 * Check availability for multiple domains.
	 *
	 * @param array<string> $domains List of domain names.
	 * @return array<string, array{available: bool, domain: string, premium: bool}>
	 */
	public function check_availability_bulk( array $domains ): array {
		$results = array();

		// Namecheap supports up to 50 domains per check.
		$chunks = array_chunk( $domains, 50 );

		foreach ( $chunks as $chunk ) {
			$result = $this->namecheap_api(
				'namecheap.domains.check',
				array( 'DomainList' => implode( ',', $chunk ) )
			);

			if ( ! $result['success'] ) {
				foreach ( $chunk as $domain ) {
					$results[ $domain ] = array(
						'available' => false,
						'domain'    => $domain,
						'premium'   => false,
					);
				}
				continue;
			}

			$xml = $result['data'];

			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Namecheap API XML response property.
			if ( isset( $xml->CommandResponse->DomainCheckResult ) ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Namecheap API XML response property.
				foreach ( $xml->CommandResponse->DomainCheckResult as $check ) {
					$attrs = $check->attributes();
					$name  = (string) ( $attrs['Domain'] ?? '' );
					$avail = 'true' === (string) ( $attrs['Available'] ?? 'false' );
					$prem  = 'true' === (string) ( $attrs['IsPremiumName'] ?? 'false' );

					$results[ $name ] = array(
						'available' => $avail,
						'domain'    => $name,
						'premium'   => $prem,
					);
				}
			}
		}

		return $results;
	}

	/**
	 * Register a new domain.
	 *
	 * @param array $params Registration parameters.
	 * @return array{success: bool, message: string, data: array}
	 */
	public function register_domain( array $params ): array {
		$domain      = $params['domain'] ?? '';
		$period      = $params['period'] ?? 1;
		$nameservers = $params['nameservers'] ?? array();
		$contact     = $params['contact'] ?? array();

		if ( empty( $domain ) ) {
			return array(
				'success' => false,
				'message' => __( 'Domain name is required.', 'hostforge' ),
				'data'    => array(),
			);
		}

		list( $sld, $tld ) = $this->split_domain( $domain );

		$api_params = array(
			'DomainName'        => $domain,
			'Years'             => $period,
			'AddFreeWhoisguard' => 'yes',
			'WGEnabled'         => 'yes',
		);

		// Add nameservers.
		if ( ! empty( $nameservers ) ) {
			foreach ( $nameservers as $i => $ns ) {
				$api_params[ 'Nameserver' . ( $i + 1 ) ] = $ns;
			}
		}

		// Add contact information for all contact types.
		foreach ( array( 'Registrant', 'Tech', 'Admin', 'AuxBilling' ) as $type ) {
			$api_params = array_merge( $api_params, $this->build_contact_params( $contact, $type ) );
		}

		/**
		 * Filters the Namecheap domain registration API parameters.
		 *
		 * Allows modification of registration parameters before they are sent
		 * to the Namecheap API (e.g. adding premium DNS, custom nameservers).
		 *
		 * @since 1.0.0
		 *
		 * @param array  $api_params The Namecheap API parameters for domain creation.
		 * @param string $domain     The domain name being registered.
		 * @param array  $params     The original registration parameters.
		 */
		$api_params = apply_filters( 'hostforge_namecheap_register_params', $api_params, $domain, $params );

		$result = $this->namecheap_api( 'namecheap.domains.create', $api_params );

		if ( ! $result['success'] ) {
			return array(
				'success' => false,
				'message' => $result['error'] ?? __( 'Domain registration failed.', 'hostforge' ),
				'data'    => array(),
			);
		}

		$xml  = $result['data'];
		$data = array();

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Namecheap API XML response property.
		if ( isset( $xml->CommandResponse->DomainCreateResult ) ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Namecheap API XML response property.
			$attrs = $xml->CommandResponse->DomainCreateResult->attributes();
			$data  = array(
				'domain_id'  => (string) ( $attrs['DomainID'] ?? '' ),
				'registered' => 'true' === (string) ( $attrs['Registered'] ?? 'false' ),
				'charged'    => (string) ( $attrs['ChargedAmount'] ?? '0' ),
				'order_id'   => (string) ( $attrs['OrderID'] ?? '' ),
			);
		}

		$this->log_info(
			'Domain registered via Namecheap.',
			array(
				'domain' => $domain,
				'data'   => $data,
			)
		);

		return array(
			'success' => true,
			'message' => __( 'Domain registered successfully.', 'hostforge' ),
			'data'    => $data,
		);
	}

	/**
	 * Transfer a domain.
	 *
	 * @param array $params Transfer parameters.
	 * @return array{success: bool, message: string}
	 */
	public function transfer_domain( array $params ): array {
		$domain   = $params['domain'] ?? '';
		$epp_code = $params['epp_code'] ?? '';
		$contact  = $params['contact'] ?? array();

		if ( empty( $domain ) || empty( $epp_code ) ) {
			return array(
				'success' => false,
				'message' => __( 'Domain name and EPP code are required for transfer.', 'hostforge' ),
			);
		}

		$api_params = array(
			'DomainName' => $domain,
			'Years'      => 1,
			'EPPCode'    => $epp_code,
		);

		$result = $this->namecheap_api( 'namecheap.domains.transfer.create', $api_params );

		if ( ! $result['success'] ) {
			return array(
				'success' => false,
				'message' => $result['error'] ?? __( 'Domain transfer initiation failed.', 'hostforge' ),
			);
		}

		$this->log_info(
			'Domain transfer initiated via Namecheap.',
			array( 'domain' => $domain )
		);

		return array(
			'success' => true,
			'message' => __( 'Domain transfer initiated. This may take 5-7 days to complete.', 'hostforge' ),
		);
	}

	/**
	 * Renew a domain.
	 *
	 * @param string $domain Domain name.
	 * @param int    $period Renewal period in years.
	 * @return array{success: bool, message: string}
	 */
	public function renew_domain( string $domain, int $period = 1 ): array {
		list( $sld, $tld ) = $this->split_domain( $domain );

		$result = $this->namecheap_api(
			'namecheap.domains.renew',
			array(
				'DomainName' => $domain,
				'Years'      => $period,
			)
		);

		if ( ! $result['success'] ) {
			return array(
				'success' => false,
				'message' => $result['error'] ?? __( 'Domain renewal failed.', 'hostforge' ),
			);
		}

		$this->log_info(
			'Domain renewed via Namecheap.',
			array(
				'domain' => $domain,
				'years'  => $period,
			)
		);

		return array(
			'success' => true,
			'message' => sprintf(
				/* translators: 1: domain name, 2: number of years */
				__( 'Domain %1$s renewed for %2$d year(s).', 'hostforge' ),
				$domain,
				$period
			),
		);
	}

	/**
	 * Get nameservers for a domain.
	 *
	 * @param string $domain Domain name.
	 * @return array{success: bool, nameservers: array<string>}
	 */
	public function get_nameservers( string $domain ): array {
		list( $sld, $tld ) = $this->split_domain( $domain );

		$result = $this->namecheap_api(
			'namecheap.domains.dns.getList',
			array(
				'SLD' => $sld,
				'TLD' => $tld,
			)
		);

		if ( ! $result['success'] ) {
			return array(
				'success'     => false,
				'nameservers' => array(),
			);
		}

		$nameservers = array();
		$xml         = $result['data'];

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Namecheap API XML response property.
		if ( isset( $xml->CommandResponse->DomainDNSGetListResult->Nameserver ) ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Namecheap API XML response property.
			foreach ( $xml->CommandResponse->DomainDNSGetListResult->Nameserver as $ns ) {
				$nameservers[] = (string) $ns;
			}
		}

		return array(
			'success'     => true,
			'nameservers' => $nameservers,
		);
	}

	/**
	 * Set nameservers for a domain.
	 *
	 * @param string        $domain      Domain name.
	 * @param array<string> $nameservers Nameserver list.
	 * @return array{success: bool, message: string}
	 */
	public function set_nameservers( string $domain, array $nameservers ): array {
		list( $sld, $tld ) = $this->split_domain( $domain );

		/**
		 * Filters the nameservers before setting them via the Namecheap API.
		 *
		 * Allows modification of the nameserver list before it is applied to a domain.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string> $nameservers The nameservers to set.
		 * @param string        $domain      The domain name.
		 */
		$nameservers = apply_filters( 'hostforge_namecheap_nameservers', $nameservers, $domain );

		$api_params = array(
			'SLD'         => $sld,
			'TLD'         => $tld,
			'Nameservers' => implode( ',', $nameservers ),
		);

		$result = $this->namecheap_api( 'namecheap.domains.dns.setCustom', $api_params );

		if ( ! $result['success'] ) {
			return array(
				'success' => false,
				'message' => $result['error'] ?? __( 'Failed to set nameservers.', 'hostforge' ),
			);
		}

		return array(
			'success' => true,
			'message' => __( 'Nameservers updated successfully.', 'hostforge' ),
		);
	}

	/**
	 * Get the domain lock status.
	 *
	 * @param string $domain Domain name.
	 * @return array{success: bool, locked: bool}
	 */
	public function get_lock( string $domain ): array {
		$result = $this->namecheap_api(
			'namecheap.domains.getRegistrarLock',
			array( 'DomainName' => $domain )
		);

		if ( ! $result['success'] ) {
			return array(
				'success' => false,
				'locked'  => false,
			);
		}

		$xml    = $result['data'];
		$locked = false;

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Namecheap API XML response property.
		if ( isset( $xml->CommandResponse->DomainGetRegistrarLockResult ) ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Namecheap API XML response property.
			$attrs  = $xml->CommandResponse->DomainGetRegistrarLockResult->attributes();
			$locked = 'true' === (string) ( $attrs['RegistrarLockStatus'] ?? 'false' );
		}

		return array(
			'success' => true,
			'locked'  => $locked,
		);
	}

	/**
	 * Toggle the domain lock.
	 *
	 * @param string $domain Domain name.
	 * @param bool   $lock   Whether to lock or unlock.
	 * @return array{success: bool, message: string}
	 */
	public function toggle_lock( string $domain, bool $lock ): array {
		$result = $this->namecheap_api(
			'namecheap.domains.setRegistrarLock',
			array(
				'DomainName' => $domain,
				'LockAction' => $lock ? 'LOCK' : 'UNLOCK',
			)
		);

		if ( ! $result['success'] ) {
			return array(
				'success' => false,
				'message' => $result['error'] ?? __( 'Failed to update lock status.', 'hostforge' ),
			);
		}

		return array(
			'success' => true,
			'message' => $lock
				? __( 'Domain locked successfully.', 'hostforge' )
				: __( 'Domain unlocked successfully.', 'hostforge' ),
		);
	}

	/**
	 * Get the EPP/authorization code.
	 *
	 * @param string $domain Domain name.
	 * @return array{success: bool, epp_code: string}
	 */
	public function get_epp_code( string $domain ): array {
		// Namecheap emails the EPP code to the registrant.
		// We can attempt to get it via the domains.getInfo call.
		$result = $this->namecheap_api(
			'namecheap.domains.getInfo',
			array( 'DomainName' => $domain )
		);

		if ( ! $result['success'] ) {
			return array(
				'success'  => false,
				'epp_code' => '',
			);
		}

		return array(
			'success'  => true,
			'epp_code' => __( 'The EPP code has been emailed to the domain registrant email address.', 'hostforge' ),
		);
	}

	/**
	 * Get WHOIS information.
	 *
	 * @param string $domain Domain name.
	 * @return array{success: bool, whois: array}
	 */
	public function get_whois( string $domain ): array {
		$result = $this->namecheap_api(
			'namecheap.domains.getContacts',
			array( 'DomainName' => $domain )
		);

		if ( ! $result['success'] ) {
			return array(
				'success' => false,
				'whois'   => array(),
			);
		}

		$xml   = $result['data'];
		$whois = array();

		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Namecheap API XML response properties.
		if ( isset( $xml->CommandResponse->DomainContactsResult ) ) {
			foreach ( array( 'Registrant', 'Tech', 'Admin', 'AuxBilling' ) as $type ) {
				if ( isset( $xml->CommandResponse->DomainContactsResult->$type ) ) {
					$contact                      = $xml->CommandResponse->DomainContactsResult->$type;
					$whois[ strtolower( $type ) ] = array(
						'first_name'   => (string) ( $contact->FirstName ?? '' ),
						'last_name'    => (string) ( $contact->LastName ?? '' ),
						'organization' => (string) ( $contact->OrganizationName ?? '' ),
						'address1'     => (string) ( $contact->Address1 ?? '' ),
						'address2'     => (string) ( $contact->Address2 ?? '' ),
						'city'         => (string) ( $contact->City ?? '' ),
						'state'        => (string) ( $contact->StateProvince ?? '' ),
						'postal_code'  => (string) ( $contact->PostalCode ?? '' ),
						'country'      => (string) ( $contact->Country ?? '' ),
						'phone'        => (string) ( $contact->Phone ?? '' ),
						'email'        => (string) ( $contact->EmailAddress ?? '' ),
					);
				}
			}
		}
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		return array(
			'success' => true,
			'whois'   => $whois,
		);
	}

	/**
	 * Update WHOIS/contact information.
	 *
	 * @param string $domain  Domain name.
	 * @param array  $contact Updated contact details.
	 * @return array{success: bool, message: string}
	 */
	public function update_whois( string $domain, array $contact ): array {
		$api_params = array(
			'DomainName' => $domain,
		);

		foreach ( array( 'Registrant', 'Tech', 'Admin', 'AuxBilling' ) as $type ) {
			$api_params = array_merge( $api_params, $this->build_contact_params( $contact, $type ) );
		}

		$result = $this->namecheap_api( 'namecheap.domains.setContacts', $api_params );

		if ( ! $result['success'] ) {
			return array(
				'success' => false,
				'message' => $result['error'] ?? __( 'Failed to update WHOIS information.', 'hostforge' ),
			);
		}

		return array(
			'success' => true,
			'message' => __( 'WHOIS information updated successfully.', 'hostforge' ),
		);
	}

	/**
	 * Get DNS records for a domain.
	 *
	 * @param string $domain Domain name.
	 * @return array{success: bool, records: array}
	 */
	public function get_dns_records( string $domain ): array {
		list( $sld, $tld ) = $this->split_domain( $domain );

		$result = $this->namecheap_api(
			'namecheap.domains.dns.getHosts',
			array(
				'SLD' => $sld,
				'TLD' => $tld,
			)
		);

		if ( ! $result['success'] ) {
			return array(
				'success' => false,
				'records' => array(),
			);
		}

		$records = array();
		$xml     = $result['data'];

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Namecheap API XML response property.
		if ( isset( $xml->CommandResponse->DomainDNSGetHostsResult->host ) ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Namecheap API XML response property.
			foreach ( $xml->CommandResponse->DomainDNSGetHostsResult->host as $host ) {
				$attrs     = $host->attributes();
				$records[] = array(
					'id'       => (int) ( $attrs['HostId'] ?? 0 ),
					'type'     => (string) ( $attrs['Type'] ?? '' ),
					'host'     => (string) ( $attrs['Name'] ?? '' ),
					'value'    => (string) ( $attrs['Address'] ?? '' ),
					'ttl'      => (int) ( $attrs['TTL'] ?? 3600 ),
					'priority' => (int) ( $attrs['MXPref'] ?? 0 ),
				);
			}
		}

		return array(
			'success' => true,
			'records' => $records,
		);
	}

	/**
	 * Add a DNS record.
	 *
	 * Namecheap requires setting ALL records at once, so we fetch existing
	 * records, add the new one, and push the complete set.
	 *
	 * @param string $domain Domain name.
	 * @param array  $record DNS record data.
	 * @return array{success: bool, message: string}
	 */
	public function add_dns_record( string $domain, array $record ): array {
		$existing = $this->get_dns_records( $domain );

		if ( ! $existing['success'] ) {
			return array(
				'success' => false,
				'message' => __( 'Failed to fetch existing DNS records.', 'hostforge' ),
			);
		}

		$records   = $existing['records'];
		$records[] = array(
			'type'     => $record['type'] ?? 'A',
			'host'     => $record['host'] ?? '@',
			'value'    => $record['value'] ?? '',
			'ttl'      => $record['ttl'] ?? 3600,
			'priority' => $record['priority'] ?? 10,
		);

		return $this->set_all_dns_records( $domain, $records );
	}

	/**
	 * Update a DNS record.
	 *
	 * Fetches all records, modifies the target, and pushes the complete set.
	 *
	 * @param string $domain    Domain name.
	 * @param int    $record_id Record ID (local or registrar).
	 * @param array  $record    Updated DNS record data.
	 * @return array{success: bool, message: string}
	 */
	public function update_dns_record( string $domain, int $record_id, array $record ): array {
		$existing = $this->get_dns_records( $domain );

		if ( ! $existing['success'] ) {
			return array(
				'success' => false,
				'message' => __( 'Failed to fetch existing DNS records.', 'hostforge' ),
			);
		}

		$records = $existing['records'];
		$found   = false;

		foreach ( $records as &$existing_record ) {
			if ( (int) ( $existing_record['id'] ?? 0 ) === $record_id ) {
				$existing_record = array_merge( $existing_record, $record );
				$found           = true;
				break;
			}
		}
		unset( $existing_record );

		if ( ! $found ) {
			return array(
				'success' => false,
				'message' => __( 'DNS record not found.', 'hostforge' ),
			);
		}

		return $this->set_all_dns_records( $domain, $records );
	}

	/**
	 * Delete a DNS record.
	 *
	 * Fetches all records, removes the target, and pushes the complete set.
	 *
	 * @param string $domain    Domain name.
	 * @param int    $record_id Record ID.
	 * @return array{success: bool, message: string}
	 */
	public function delete_dns_record( string $domain, int $record_id ): array {
		$existing = $this->get_dns_records( $domain );

		if ( ! $existing['success'] ) {
			return array(
				'success' => false,
				'message' => __( 'Failed to fetch existing DNS records.', 'hostforge' ),
			);
		}

		$records = array_filter(
			$existing['records'],
			function ( $r ) use ( $record_id ) {
				return (int) ( $r['id'] ?? 0 ) !== $record_id;
			}
		);

		if ( count( $records ) === count( $existing['records'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'DNS record not found.', 'hostforge' ),
			);
		}

		return $this->set_all_dns_records( $domain, array_values( $records ) );
	}

	/**
	 * Enable auto-renewal for a domain.
	 *
	 * @param string $domain Domain name.
	 * @return array{success: bool, message: string}
	 */
	public function enable_auto_renew( string $domain ): array {
		return array(
			'success' => true,
			'message' => __( 'Auto-renewal enabled. HostForge will automatically renew this domain before expiry.', 'hostforge' ),
		);
	}

	/**
	 * Disable auto-renewal for a domain.
	 *
	 * @param string $domain Domain name.
	 * @return array{success: bool, message: string}
	 */
	public function disable_auto_renew( string $domain ): array {
		return array(
			'success' => true,
			'message' => __( 'Auto-renewal disabled.', 'hostforge' ),
		);
	}

	/**
	 * Set all DNS records at once (Namecheap requirement).
	 *
	 * @param string $domain  Domain name.
	 * @param array  $records Complete set of DNS records.
	 * @return array{success: bool, message: string}
	 */
	private function set_all_dns_records( string $domain, array $records ): array {
		list( $sld, $tld ) = $this->split_domain( $domain );

		$api_params = array(
			'SLD' => $sld,
			'TLD' => $tld,
		);

		foreach ( $records as $i => $record ) {
			$n                              = $i + 1;
			$api_params[ "HostName{$n}" ]   = $record['host'] ?? '@';
			$api_params[ "RecordType{$n}" ] = $record['type'] ?? 'A';
			$api_params[ "Address{$n}" ]    = $record['value'] ?? '';
			$api_params[ "TTL{$n}" ]        = $record['ttl'] ?? 3600;

			if ( in_array( $record['type'] ?? '', array( 'MX', 'SRV' ), true ) ) {
				$api_params[ "MXPref{$n}" ] = $record['priority'] ?? 10;
			}
		}

		$result = $this->namecheap_api( 'namecheap.domains.dns.setHosts', $api_params );

		if ( ! $result['success'] ) {
			return array(
				'success' => false,
				'message' => $result['error'] ?? __( 'Failed to update DNS records.', 'hostforge' ),
			);
		}

		return array(
			'success' => true,
			'message' => __( 'DNS records updated successfully.', 'hostforge' ),
		);
	}

	/**
	 * Make a Namecheap API call.
	 *
	 * @param string $command Namecheap API command.
	 * @param array  $params  Additional parameters.
	 * @return array{success: bool, data: \SimpleXMLElement|null, error: string}
	 */
	private function namecheap_api( string $command, array $params = array() ): array {
		$default_params = array(
			'ApiUser'  => $this->api_user,
			'ApiKey'   => $this->api_key,
			'UserName' => $this->username,
			'ClientIp' => $this->client_ip,
			'Command'  => $command,
		);

		$query_params = array_merge( $default_params, $params );
		$url          = $this->base_url . '?' . http_build_query( $query_params );

		$response = wp_remote_get(
			$url,
			array(
				'timeout'   => $this->timeout,
				'sslverify' => true,
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->log_error(
				'Namecheap API request failed',
				array(
					'command' => $command,
					'error'   => $response->get_error_message(),
				)
			);

			return array(
				'success' => false,
				'data'    => null,
				'error'   => $response->get_error_message(),
			);
		}

		$code     = wp_remote_retrieve_response_code( $response );
		$body_raw = wp_remote_retrieve_body( $response );

		if ( $code < 200 || $code >= 300 ) {
			$this->log_warning(
				'Namecheap API returned non-2xx status',
				array(
					'command' => $command,
					'code'    => $code,
				)
			);

			return array(
				'success' => false,
				'data'    => null,
				'error'   => sprintf(
					/* translators: %d: HTTP status code */
					__( 'Namecheap API returned HTTP %d.', 'hostforge' ),
					$code
				),
			);
		}

		$xml = $this->parse_xml_response( $body_raw );

		if ( null === $xml ) {
			return array(
				'success' => false,
				'data'    => null,
				'error'   => __( 'Failed to parse Namecheap API response.', 'hostforge' ),
			);
		}

		$status = (string) ( $xml->attributes()['Status'] ?? 'ERROR' );

		if ( 'OK' !== $status ) {
			$error_msg = __( 'Namecheap API error.', 'hostforge' );

			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Namecheap API XML response property.
			if ( isset( $xml->Errors->Error ) ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Namecheap API XML response property.
				$error_msg = (string) $xml->Errors->Error;
			}

			$this->log_error(
				'Namecheap API error',
				array(
					'command' => $command,
					'status'  => $status,
					'error'   => $error_msg,
				)
			);

			return array(
				'success' => false,
				'data'    => $xml,
				'error'   => $error_msg,
			);
		}

		$api_result = array(
			'success' => true,
			'data'    => $xml,
			'error'   => '',
		);

		/**
		 * Filters the Namecheap API response before it is returned.
		 *
		 * Allows inspection or modification of any successful Namecheap API response.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $api_result The API result array with success, data (SimpleXMLElement), error keys.
		 * @param string $command    The Namecheap API command that was called.
		 * @param array  $params     The parameters sent to the API (excluding credentials).
		 */
		$api_result = apply_filters( 'hostforge_namecheap_api_response', $api_result, $command, $params );

		return $api_result;
	}

	/**
	 * Parse XML response body.
	 *
	 * @param string $body Raw XML response.
	 * @return \SimpleXMLElement|null
	 */
	private function parse_xml_response( string $body ): ?\SimpleXMLElement {
		if ( empty( $body ) ) {
			return null;
		}

		// Suppress XML parsing warnings.
		$use_errors = libxml_use_internal_errors( true );

		$xml = simplexml_load_string( $body );

		libxml_use_internal_errors( $use_errors );

		if ( false === $xml ) {
			$this->log_error(
				'Failed to parse XML response',
				array( 'body' => substr( $body, 0, 500 ) )
			);
			return null;
		}

		return $xml;
	}

	/**
	 * Split a domain into SLD and TLD parts.
	 *
	 * @param string $domain Full domain name (e.g. example.com).
	 * @return array{0: string, 1: string} [SLD, TLD].
	 */
	private function split_domain( string $domain ): array {
		$parts = explode( '.', $domain, 2 );

		return array(
			$parts[0] ?? '',
			$parts[1] ?? '',
		);
	}

	/**
	 * Build Namecheap contact parameters for a contact type.
	 *
	 * @param array  $contact Contact data.
	 * @param string $type    Contact type (Registrant, Tech, Admin, AuxBilling).
	 * @return array<string, string>
	 */
	private function build_contact_params( array $contact, string $type ): array {
		return array(
			$type . 'FirstName'        => $contact['first_name'] ?? '',
			$type . 'LastName'         => $contact['last_name'] ?? '',
			$type . 'Address1'         => $contact['address1'] ?? '',
			$type . 'Address2'         => $contact['address2'] ?? '',
			$type . 'City'             => $contact['city'] ?? '',
			$type . 'StateProvince'    => $contact['state'] ?? '',
			$type . 'PostalCode'       => $contact['postal_code'] ?? '',
			$type . 'Country'          => $contact['country'] ?? '',
			$type . 'Phone'            => $contact['phone'] ?? '',
			$type . 'EmailAddress'     => $contact['email'] ?? '',
			$type . 'OrganizationName' => $contact['organization'] ?? '',
		);
	}
}
