<?php

namespace local_lti\imsglobal\lti\oauth;

/**
 * Class to represent an %OAuth Signature Method
 *
 * @copyright  Andy Smith
 * @version 2008-08-04
 * @license https://opensource.org/licenses/MIT The MIT License
 */
/**
 * A class for implementing a Signature Method
 * See section 9 ("Signing Requests") in the spec
 */
abstract class signature_method {
    /**
     * Needs to return the name of the Signature Method (ie HMAC-SHA1)
     * @return string
     */
    abstract public function get_name();

    /**
     * Build up the signature
     * NOTE: The output of this function MUST NOT be urlencoded.
     * the encoding is handled in request when the final
     * request is serialized
     * @param request $request
     * @param consumer $consumer
     * @param token $token
     * @return string
     */
    abstract public function build_signature($request, $consumer, $token);

    /**
     * Verifies that a given signature is correct
     * @param request $request
     * @param consumer $consumer
     * @param token $token
     * @param string $signature
     * @return bool
     */
    public function check_signature($request, $consumer, $token, $signature) {

        $built = $this->build_signature($request, $consumer, $token);

        // Check for zero length, although unlikely here
        if (strlen($built) == 0 || strlen($signature) == 0) {
            return false;
        }

        if (strlen($built) != strlen($signature)) {
            return false;
        }

        // Avoid a timing leak with a (hopefully) time insensitive compare
        $result = 0;
        for ($i = 0; $i < strlen($signature); $i++) {
            $result |= ord($built{$i}) ^ ord($signature{$i});
        }

        return $result == 0;

    }

}
