<?php

namespace Kronos\FileSystem\Mount\S3;

use Elao\Enum\AutoDiscoveredValuesTrait;
use Elao\Enum\Enum;

class SupportedOptionsEnum extends Enum
{
    use AutoDiscoveredValuesTrait;

    public const ACL = 'ACL';
    public const CACHE_CONTROL = 'CacheControl';
    public const CONTENT_DISPOSITION = 'ContentDisposition';
    public const CONTENT_ENCODING = 'ContentEncoding';
    public const CONTENT_LENGTH = 'ContentLength';
    public const CONTENT_TYPE = 'ContentType';
    public const EXPIRES = 'Expires';
    public const GRANT_FULL_CONTROL = 'GrantFullControl';
    public const GRANT_READ = 'GrantRead';
    public const GRANT_READ_ACP = 'GrantReadACP';
    public const GRANT_WRITE_ACP = 'GrantWriteACP';
    public const METADATA = 'Metadata';
    public const REQUEST_PAYER = 'RequestPayer';
    public const SSE_CUSTOMER_ALGORITHM = 'SSECustomerAlgorithm';
    public const SSE_CUSTOMER_KEY = 'SSECustomerKey';
    public const SSE_CUSTOMER_KEY_MD5 = 'SSECustomerKeyMD5';
    public const SSE_KMS_KEY_ID = 'SSEKMSKeyId';
    public const SERVER_SIDE_ENCRYPTION = 'ServerSideEncryption';
    public const STORAGE_CLASS = 'StorageClass';
    public const TAGGING = 'Tagging';
    public const WEBSITE_REDIRECT_LOCATION = 'WebsiteRedirectLocation';
}
