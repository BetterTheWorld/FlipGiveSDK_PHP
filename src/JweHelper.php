<?php
namespace FlipGive\ShopCloud;

use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A128GCM;
use Jose\Component\Encryption\Algorithm\KeyEncryption\Dir;
use Jose\Component\Encryption\Compression\CompressionMethodManager;
use Jose\Component\Encryption\Compression\Deflate;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Encryption\JWEDecrypter;
use Jose\Component\Encryption\Serializer\CompactSerializer;
use Jose\Component\Encryption\Serializer\JWESerializerManager;

class JweHelper
{
    private $jweBuilder;
    private $jweDecrypter;
    private $key;
    private $serializerManager;

    public function __construct($secret)
    {
        $keyEncryptionAlgorithmManager = new AlgorithmManager([ new Dir() ]);
        $contentEncryptionAlgorithmManager = new AlgorithmManager([ new A128GCM() ]);
        $compressionMethodManager = new CompressionMethodManager([ new Deflate(0) ]);

        $this->jweBuilder = new JWEBuilder(
            $keyEncryptionAlgorithmManager,
            $contentEncryptionAlgorithmManager,
            $compressionMethodManager
        );

        $this->jweDecrypter = new JWEDecrypter(
            $keyEncryptionAlgorithmManager,
            $contentEncryptionAlgorithmManager,
            $compressionMethodManager
        );

        $this->key = new JWK(
            [
                'kty' => 'oct',
                'k' => base64_encode($secret),
            ]
        );

        $this->serializerManager = new JWESerializerManager([ new CompactSerializer() ]);
    }

    public function encrypt($payload)
    {
        $jwe = $this->jweBuilder
            ->create()
            ->withPayload(json_encode($payload))
            ->withSharedProtectedHeader(
                [
                    'alg' => 'dir',
                    'enc' => 'A128GCM',
                ]
            )
            ->addRecipient($this->key)
            ->build();

        return $this->serializerManager->serialize('jwe_compact', $jwe, 0);
    }

    public function decrypt($token)
    {
        $jwe = $this->serializerManager->unserialize($token);

        $this->jweDecrypter->decryptUsingKey($jwe, $this->key, 0);

        return $jwe->getPayload();
    }
}
