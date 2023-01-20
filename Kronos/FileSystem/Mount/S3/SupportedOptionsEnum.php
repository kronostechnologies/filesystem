<?php

namespace Kronos\FileSystem\Mount\S3;

enum SupportedOptionsEnum: string
{
    case ACL = 'ACL';
    case CACHE_CONTROL = 'CacheControl';
    case CONTENT_DISPOSITION = 'ContentDisposition';
    case CONTENT_ENCODING = 'ContentEncoding';
    case CONTENT_LENGTH = 'ContentLength';
    case CONTENT_TYPE = 'ContentType';
    case EXPIRES = 'Expires';
    case GRANT_FULL_CONTROL = 'GrantFullControl';
    case GRANT_READ = 'GrantRead';
    case GRANT_READ_ACP = 'GrantReadACP';
    case GRANT_WRITE_ACP = 'GrantWriteACP';
    case METADATA = 'Metadata';
    case REQUEST_PAYER = 'RequestPayer';
    case SSE_CUSTOMER_ALGORITHM = 'SSECustomerAlgorithm';
    case SSE_CUSTOMER_KEY = 'SSECustomerKey';
    case SSE_CUSTOMER_KEY_MD5 = 'SSECustomerKeyMD5';
    case SSE_KMS_KEY_ID = 'SSEKMSKeyId';
    case SERVER_SIDE_ENCRYPTION = 'ServerSideEncryption';
    case STORAGE_CLASS = 'StorageClass';
    case TAGGING = 'Tagging';
    case WEBSITE_REDIRECT_LOCATION = 'WebsiteRedirectLocation';
}
