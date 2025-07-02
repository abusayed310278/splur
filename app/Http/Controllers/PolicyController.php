<?php

namespace App\Http\Controllers;

use App\Models\Policy;
use Illuminate\Http\Request;
use Exception;

class PolicyController extends Controller
{
    public function getPrivacyPolicy()
    {
        try {
            $policy = Policy::first();

            if (!$policy || !$policy->privacy_policy) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Privacy policy not found.'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'privacy_policy' => $policy->privacy_policy,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch privacy policy.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getTermsConditions()
    {
        try {
            $policy = Policy::first();

            if (!$policy || !$policy->terms_conditions) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Terms and conditions not found.'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'terms_conditions' => $policy->terms_conditions,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch terms and conditions.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getCookiesPolicy()
    {
        try {
            $policy = Policy::first();

            if (!$policy || !$policy->cookies_policy) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cookies policy not found.'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'cookies_policy' => $policy->cookies_policy,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch cookies policy.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getInvestmentDisclaimer()
    {
        try {
            $policy = Policy::first();

            if (!$policy || !$policy->investment_disclaimer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Investment disclaimer not found.'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'investment_disclaimer' => $policy->investment_disclaimer,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch investment disclaimer.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function storeOrUpdatePrivacyPolicy(Request $request)
    {
        $request->validate([
            'privacy_policy' => 'required|string',
        ]);

        try {
            // Get the first policy or create a new instance
            $policy = Policy::firstOrNew();

            // Update the field
            $policy->privacy_policy = $request->privacy_policy;
            $policy->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Privacy policy saved successfully.',
                'data' => $policy->privacy_policy
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save privacy policy.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function storeOrUpdateTermsConditions(Request $request)
    {
        $request->validate([
            'terms_conditions' => 'required|string',
        ]);

        try {
            $policy = Policy::firstOrNew();

            $policy->terms_conditions = $request->terms_conditions;
            $policy->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Terms and conditions saved successfully.',
                'data' => $policy->terms_conditions
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save terms and conditions.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function storeOrUpdateCookiesPolicy(Request $request)
    {
        $request->validate([
            'cookies_policy' => 'required|string',
        ]);

        try {
            $policy = Policy::firstOrNew();

            $policy->cookies_policy = $request->cookies_policy;
            $policy->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Cookies policy saved successfully.',
                'data' => $policy->cookies_policy
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save cookies policy.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function storeOrUpdateInvestmentDisclaimer(Request $request)
    {
        $request->validate([
            'investment_disclaimer' => 'required|string',
        ]);

        try {
            $policy = Policy::firstOrNew();

            $policy->investment_disclaimer = $request->investment_disclaimer;
            $policy->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Investment disclaimer saved successfully.',
                'data' => $policy->investment_disclaimer
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save investment disclaimer.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
