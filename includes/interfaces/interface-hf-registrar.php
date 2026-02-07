<?php
/**
 * Registrar Interface.
 *
 * Contract for domain registrar providers (OpenProvider, Namecheap, etc.).
 *
 * @package HostForge\Interfaces
 */

namespace HostForge\Interfaces;

defined( 'ABSPATH' ) || exit;

/**
 * Interface HF_Registrar
 */
interface HF_Registrar {

	/**
	 * Check domain availability.
	 *
	 * @param string $domain Full domain name (e.g. example.com).
	 * @return array{available: bool, domain: string, premium: bool}
	 */
	public function check_availability( string $domain ): array;

	/**
	 * Check availability for multiple domains at once.
	 *
	 * @param array<string> $domains List of domain names.
	 * @return array<string, array{available: bool, domain: string, premium: bool}>
	 */
	public function check_availability_bulk( array $domains ): array;

	/**
	 * Register a new domain.
	 *
	 * @param array $params {
	 *     Registration parameters.
	 *     @type string $domain      Domain name.
	 *     @type int    $period      Registration period in years.
	 *     @type array  $nameservers Nameserver list.
	 *     @type array  $contact     Contact information.
	 * }
	 * @return array{success: bool, message: string, data: array}
	 */
	public function register_domain( array $params ): array;

	/**
	 * Transfer a domain.
	 *
	 * @param array $params {
	 *     Transfer parameters.
	 *     @type string $domain   Domain name.
	 *     @type string $epp_code Authorization/EPP code.
	 *     @type array  $contact  Contact information.
	 * }
	 * @return array{success: bool, message: string}
	 */
	public function transfer_domain( array $params ): array;

	/**
	 * Renew a domain.
	 *
	 * @param string $domain Domain name.
	 * @param int    $period Renewal period in years.
	 * @return array{success: bool, message: string}
	 */
	public function renew_domain( string $domain, int $period = 1 ): array;

	/**
	 * Get nameservers for a domain.
	 *
	 * @param string $domain Domain name.
	 * @return array{success: bool, nameservers: array<string>}
	 */
	public function get_nameservers( string $domain ): array;

	/**
	 * Set nameservers for a domain.
	 *
	 * @param string        $domain      Domain name.
	 * @param array<string> $nameservers Nameserver list.
	 * @return array{success: bool, message: string}
	 */
	public function set_nameservers( string $domain, array $nameservers ): array;

	/**
	 * Get the domain lock status.
	 *
	 * @param string $domain Domain name.
	 * @return array{success: bool, locked: bool}
	 */
	public function get_lock( string $domain ): array;

	/**
	 * Toggle the domain lock.
	 *
	 * @param string $domain Domain name.
	 * @param bool   $lock   Whether to lock or unlock.
	 * @return array{success: bool, message: string}
	 */
	public function toggle_lock( string $domain, bool $lock ): array;

	/**
	 * Get the EPP/authorization code.
	 *
	 * @param string $domain Domain name.
	 * @return array{success: bool, epp_code: string}
	 */
	public function get_epp_code( string $domain ): array;

	/**
	 * Get WHOIS information.
	 *
	 * @param string $domain Domain name.
	 * @return array{success: bool, whois: array}
	 */
	public function get_whois( string $domain ): array;

	/**
	 * Update WHOIS/contact information.
	 *
	 * @param string $domain  Domain name.
	 * @param array  $contact Updated contact details.
	 * @return array{success: bool, message: string}
	 */
	public function update_whois( string $domain, array $contact ): array;

	/**
	 * Get DNS records for a domain.
	 *
	 * @param string $domain Domain name.
	 * @return array{success: bool, records: array}
	 */
	public function get_dns_records( string $domain ): array;

	/**
	 * Add a DNS record.
	 *
	 * @param string $domain Domain name.
	 * @param array  $record DNS record data (type, name, value, ttl, priority).
	 * @return array{success: bool, message: string}
	 */
	public function add_dns_record( string $domain, array $record ): array;

	/**
	 * Update a DNS record.
	 *
	 * @param string $domain    Domain name.
	 * @param int    $record_id Record ID.
	 * @param array  $record    Updated DNS record data.
	 * @return array{success: bool, message: string}
	 */
	public function update_dns_record( string $domain, int $record_id, array $record ): array;

	/**
	 * Delete a DNS record.
	 *
	 * @param string $domain    Domain name.
	 * @param int    $record_id Record ID.
	 * @return array{success: bool, message: string}
	 */
	public function delete_dns_record( string $domain, int $record_id ): array;

	/**
	 * Enable auto-renewal for a domain.
	 *
	 * @param string $domain Domain name.
	 * @return array{success: bool, message: string}
	 */
	public function enable_auto_renew( string $domain ): array;

	/**
	 * Disable auto-renewal for a domain.
	 *
	 * @param string $domain Domain name.
	 * @return array{success: bool, message: string}
	 */
	public function disable_auto_renew( string $domain ): array;
}
