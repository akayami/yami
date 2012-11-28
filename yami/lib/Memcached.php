<?php
class Memcached extends Memcache {
	
	const OPT_COMPRESSION = -1001;
	const OPT_SERIALIZER = -1003;
	const SERIALIZER_PHP = 1;
	const SERIALIZER_IGBINARY = 2;
	const SERIALIZER_JSON = 3;
	const OPT_PREFIX_KEY = -1002;
	const OPT_HASH = 2;
	const HASH_DEFAULT = 0;
	const HASH_MD5 = 1;
	const HASH_CRC = 2;
	const HASH_FNV1_64 = 3;
	const HASH_FNV1A_64 = 4;
	const HASH_FNV1_32 = 5;
	const HASH_FNV1A_32 = 6;
	const HASH_HSIEH = 7;
	const HASH_MURMUR = 8;
	const OPT_DISTRIBUTION = 9;
	const DISTRIBUTION_MODULA = 0;
	const DISTRIBUTION_CONSISTENT = 1;
	const OPT_LIBKETAMA_COMPATIBLE = 16;
	const OPT_BUFFER_WRITES = 10;
	const OPT_BINARY_PROTOCOL = 18;
	const OPT_NO_BLOCK = 0;
	const OPT_TCP_NODELAY = 1;
	const OPT_SOCKET_SEND_SIZE = 4;
	const OPT_SOCKET_RECV_SIZE = 5;
	const OPT_CONNECT_TIMEOUT = 14;
	const OPT_RETRY_TIMEOUT = 15;
	const OPT_SEND_TIMEOUT = 19;
	const OPT_RECV_TIMEOUT = 15;
	const OPT_POLL_TIMEOUT = 8;
	const OPT_CACHE_LOOKUPS = 6;
	const OPT_SERVER_FAILURE_LIMIT = 21;
//	const HAVE_IGBINARY = #&UNDEFINED;#;
//	const HAVE_JSON = #&UNDEFINED;#;
	const GET_PRESERVE_ORDER = 1;
	const RES_SUCCESS = 0;
	const RES_FAILURE = 1;
	const RES_HOST_LOOKUP_FAILURE = 2;
	const RES_UNKNOWN_READ_FAILURE = 7;
	const RES_PROTOCOL_ERROR = 8;
	const RES_CLIENT_ERROR = 9;
	const RES_SERVER_ERROR = 10;
	const RES_WRITE_FAILURE = 5;
	const RES_DATA_EXISTS = 12;
	const RES_NOTSTORED = 14;
	const RES_NOTFOUND = 16;
	const RES_PARTIAL_READ = 18;
	const RES_SOME_ERRORS = 19;
	const RES_NO_SERVERS = 20;
	const RES_END = 21;
	const RES_ERRNO = 26;
	const RES_BUFFERED = 32;
	const RES_TIMEOUT = 31;
	const RES_BAD_KEY_PROVIDED = 33;
	const RES_CONNECTION_SOCKET_CREATE_FAILURE = 11;
	const RES_PAYLOAD_FAILURE = -1001;
	
	
	public function addServers(array $servers) {
		foreach($servers as $server) {
			$this->addserver($server[0], $server[1]);
		}
	}
}