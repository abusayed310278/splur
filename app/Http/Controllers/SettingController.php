<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Content;
use App\Models\Footer;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

class SettingController extends Controller
{
    public function getUserContent($user_id)
    {
        try {
            $contents = Content::with(['category:id,category_name', 'subcategory:id,name'])
                ->where('user_id', $user_id)
                ->latest()
                ->get();

            // Append image URLs and category/subcategory names
            $contents->transform(function ($content) {
                $content->image1_url = $content->image1 ? url($content->image1) : null;
                $content->advertising_image_url = $content->advertising_image ? url($content->advertising_image) : null;

                $content->category_name = $content->category?->category_name;
                $content->sub_category_name = $content->subcategory?->name;

                unset($content->category, $content->subcategory);  // optional: remove relations if not needed

                return $content;
            });

            return response()->json([
                'status' => true,
                'message' => 'User content fetched successfully.',
                'data' => $contents,
            ]);
        } catch (Exception $e) {
            Log::error('Fetching user content failed: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch user content.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getFooter()
    {
        try {
            $footerLinks = Footer::select('name', 'named_url')->get()->toArray();
            $setting = Setting::where('key', 'footer')->first();

            return response()->json([
                'success' => true,
                'message' => 'Footer data fetched successfully.',
                'data' => [
                    'footer_links' => $footerLinks,
                    'facebook_link' => $setting->facebook_link ?? null,
                    'instagram_link' => $setting->instagram_link ?? null,
                    'linkedin_link' => $setting->linkedin_link ?? null,
                    'twitter_link' => $setting->twitter_link ?? null,
                    'app_store_link' => $setting->app_store_link ?? null,
                    'google_play_link' => $setting->google_play_link ?? null,
                    'bg_color' => $setting->bg_color ?? null,  // <-- added background color
                ]
            ]);
        } catch (Exception $e) {
            Log::error('getFooter Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch footer data.',
                'error' => app()->environment('production') ? 'Internal server error' : $e->getMessage(),
            ], 500);
        }
    }

    public function getHeader()
    {
        $setting = Setting::where('key', 'header')->first();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Header settings not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Header settings fetched successfully.',
            'data' => [
                'logo' => $setting->logo,
                'border_color' => $setting->border_color,
                'bg_color' => $setting->color,
                'menu_item_color' => $setting->menu_item_color,
                'menu_item_active_color' => $setting->menu_item_active_color,
            ],
        ]);
    }

    public function storeOrUpdateHeader(Request $request)
    {
        $request->validate([
            'border_color' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'menu_item_color' => 'nullable|string|max:255',
            'menu_item_active_color' => 'nullable|string|max:255',
            'logo' => 'nullable|image',
        ]);

        $setting = Setting::firstOrNew(['key' => 'header']);

        $setting->border_color = $request->border_color;
        $setting->color = $request->color;
        $setting->menu_item_color = $request->menu_item_color;
        $setting->menu_item_active_color = $request->menu_item_active_color;
        // $setting->color = $request->color;

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $logoName = time() . '_logo.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/Header'), $logoName);
            $setting->logo = 'uploads/Header/' . $logoName;
        }

        $setting->save();

        return response()->json([
            'success' => true,
            'message' => 'Header settings updated successfully.',
            'data' => $setting,
        ]);
    }

    public function storeOrUpdateFooter(Request $request)
    {
        $request->validate([
            'facebook_icon' => 'nullable|image',
            'facebook_link' => 'nullable|url',
            'twitter_icon' => 'nullable|image',
            'twitter_link' => 'nullable|url',
            'linkedin_icon' => 'nullable|image',
            'linkedin_link' => 'nullable|url',
            'instagram_icon' => 'nullable|image',
            'instagram_link' => 'nullable|url',
            'app_store_icon' => 'nullable|image',
            'app_store_link' => 'nullable|url',
            'google_play_icon' => 'nullable|image',
            'google_play_link' => 'nullable|url',
            'copyright' => 'nullable|string',
            'bg_color' => 'nullable|string',
        ]);

        $setting = Setting::firstOrNew(['key' => 'footer']);

        // Handle each icon upload if present
        foreach ([
            'facebook_icon', 'twitter_icon', 'linkedin_icon', 'instagram_icon',
            'app_store_icon', 'google_play_icon'
        ] as $iconField) {
            if ($request->hasFile($iconField)) {
                $file = $request->file($iconField);
                $fileName = time() . '_' . $iconField . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/Footer'), $fileName);
                $setting->$iconField = 'uploads/Footer/' . $fileName;
            }
        }

        // Set links and copyright
        $setting->facebook_link = $request->facebook_link;
        $setting->twitter_link = $request->twitter_link;
        $setting->linkedin_link = $request->linkedin_link;
        $setting->instagram_link = $request->instagram_link;
        $setting->app_store_link = $request->app_store_link;
        $setting->google_play_link = $request->google_play_link;
        $setting->copyright = $request->copyright;
        $setting->bg_color = $request->bg_color;

        $setting->save();

        return response()->json([
            'success' => true,
            'message' => 'Footer settings updated successfully.',
            'data' => $setting,
        ]);
    }

    public function footer()
    {
        $categories = Category::with('subCategories')->get();

        $data = $categories->map(function ($category) {
            return [
                'category' => $category->category_name,
                'sub_categories' => $category->subCategories->pluck('name'),
            ];
        });

        // Get the color setting
        $color = Setting::value('color');

        return response()->json([
            'success' => true,
            'color' => $color,
            'data' => $data
        ]);
    }

    public function storeOrUpdateColor(Request $request)
    {
        $request->validate([
            'color' => 'required|string|max:255',
        ]);

        // Assuming you are storing color as a column directly in the settings table
        $setting = Setting::first();  // Or use updateOrCreate for specific setting row

        if (!$setting) {
            $setting = new Setting();
        }

        $setting->color = $request->color;
        $setting->save();

        return response()->json([
            'success' => true,
            'message' => 'Color updated successfully.',
            'color' => $setting->color,
        ]);
    }

    public function showColor()
    {
        $color = Setting::first()?->color;

        if (!$color) {
            return response()->json([
                'success' => false,
                'message' => 'No color setting found.',
                'color' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Color fetched successfully.',
            'color' => $color,
        ]);
    }

    public function storeOrUpdatePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:6',
                'confirm_new_password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 400);
            }

            if ($request->new_password !== $request->confirm_new_password) {
                return response()->json([
                    'success' => false,
                    'message' => 'New password and confirmation do not match.',
                ], 400);
            }

            $user = Auth::user();

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The current password is incorrect.',
                ], 403);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Password updated successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating password: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the password.',
            ], 500);
        }
    }

    public function storeOrUpdatePasswordForUser(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:6',
                'confirm_new_password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 400);
            }

            if ($request->new_password !== $request->confirm_new_password) {
                return response()->json([
                    'success' => false,
                    'message' => 'New password and confirmation do not match.',
                ], 400);
            }

            $user = Auth::user();

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The current password is incorrect.',
                ], 403);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Password updated successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating password: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the password.',
            ], 500);
        }
    }

    public function index()
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Please login first.'
            ], 401);
        }

        $user = Auth::user();

        return response()->json([
            'success' => true,
            'message' => 'User settings fetched successfully.',
            'data' => [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'phone' => $user->phone,
                'email' => $user->email,
                'country' => $user->country,
                'city' => $user->city,
                'description' => $user->description,
                'instagram_icon' => $user->instagram_icon,
                'instagram_link' => $user->instagram_link,
                'facebook_icon' => $user->facebook_icon,
                'facebook_link' => $user->facebook_link,
                'youtube_icon' => $user->youtube_icon,
                'youtube_link' => $user->youtube_link,
                'twitter_icon' => $user->twitter_icon,
                'twitter_link' => $user->twitter_link,
                'profile_pic' => $user->profile_pic
                    ? url('uploads/ProfilePics/' . $user->profile_pic)
                    : null,
            ]
        ]);
    }

    public function showsForUser()
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Please login first.'
            ], 401);
        }

        $user = auth()->user();

        return response()->json([
            'success' => true,
            'message' => 'User settings fetched successfully.',
            'data' => [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'phone' => $user->phone,
                'email' => $user->email,
                'country' => $user->country,
                'city' => $user->city,
                'road' => $user->road,
                'postal_code' => $user->postal_code,
            ]
        ]);
    }

    public function storeOrUpdate(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login first.'
            ], 401);
        }

        try {
            $validated = $request->validate([
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255|unique:users,email,' . Auth::id(),
                'country' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:255',
                'profile_pic' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:10240',  // 10MB
                // New fields
                'description' => 'nullable|string',
                'instagram_icon' => 'nullable|string|max:255',
                'instagram_link' => 'nullable|url|max:255',
                'facebook_icon' => 'nullable|string|max:255',
                'facebook_link' => 'nullable|url|max:255',
                'youtube_icon' => 'nullable|string|max:255',
                'youtube_link' => 'nullable|url|max:255',
                'twitter_icon' => 'nullable|string|max:255',
                'twitter_link' => 'nullable|url|max:255',
            ]);

            $user = Auth::user();

            // Update fields
            foreach ($validated as $key => $value) {
                if ($key !== 'profile_pic') {
                    $user->$key = $value ?? $user->$key;
                }
            }

            // Handle profile picture upload
            if ($request->hasFile('profile_pic')) {
                // Delete old profile picture if exists
                if ($user->profile_pic && file_exists(public_path('uploads/ProfilePics/' . $user->profile_pic))) {
                    unlink(public_path('uploads/ProfilePics/' . $user->profile_pic));
                }

                $profilePic = $request->file('profile_pic');
                $profilePicName = time() . '_profile.' . $profilePic->getClientOriginalExtension();
                $profilePic->move(public_path('uploads/ProfilePics'), $profilePicName);
                $user->profile_pic = $profilePicName;
            }

            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully.',
                'data' => [
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'country' => $user->country,
                    'city' => $user->city,
                    'description' => $user->description,
                    'instagram_icon' => $user->instagram_icon,
                    'instagram_link' => $user->instagram_link,
                    'facebook_icon' => $user->facebook_icon,
                    'facebook_link' => $user->facebook_link,
                    'youtube_icon' => $user->youtube_icon,
                    'youtube_link' => $user->youtube_link,
                    'twitter_icon' => $user->twitter_icon,
                    'twitter_link' => $user->twitter_link,
                    'profile_pic' => $user->profile_pic
                        ? url('uploads/ProfilePics/' . $user->profile_pic)
                        : null,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating user profile: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // public function storeOrUpdateProfilePic(Request $request)
    // {
    //     if (!Auth::check()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Please login first.'
    //         ], 401);
    //     }

    //     try {
    //         $validated = $request->validate([
    //             'profile_pic' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:10240',
    //         ]);

    //         $user = Auth::user();

    //         // Only update if a new profile picture is uploaded
    //         if ($request->hasFile('profile_pic')) {
    //             // Delete the old one if it exists
    //             if ($user->profile_pic && file_exists(public_path('uploads/ProfilePics/' . $user->profile_pic))) {
    //                 unlink(public_path('uploads/ProfilePics/' . $user->profile_pic));
    //             }

    //             // Save the new profile picture
    //             $profilePic = $request->file('profile_pic');
    //             $profilePicName = time() . '_profile.' . $profilePic->getClientOriginalExtension();
    //             $profilePic->move(public_path('uploads/ProfilePics'), $profilePicName);
    //             $user->profile_pic = $profilePicName;

    //             $user->save();
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'message' => $request->hasFile('profile_pic')
    //                 ? 'Profile picture updated successfully.'
    //                 : 'Profile picture unchanged.',
    //             'profile_pic' => $user->profile_pic
    //                 ? url('uploads/ProfilePics/' . $user->profile_pic)
    //                 : null,
    //         ]);
    //     } catch (Exception $e) {
    //         Log::error('Error updating profile picture: ' . $e->getMessage());

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to update profile picture.',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function profileUpdateOrStore(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login first.'
            ], 401);
        }

        try {
            $validated = $request->validate([
                'profile_pic' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:10240',
            ]);

            $user = Auth::user();

            // Only update if a new profile picture is uploaded
            if ($request->hasFile('profile_pic')) {
                // Delete the old one if it exists
                if ($user->profile_pic && file_exists(public_path('uploads/ProfilePics/' . $user->profile_pic))) {
                    unlink(public_path('uploads/ProfilePics/' . $user->profile_pic));
                }

                // Save the new profile picture
                $profilePic = $request->file('profile_pic');
                $profilePicName = time() . '_profile.' . $profilePic->getClientOriginalExtension();
                $profilePic->move(public_path('uploads/ProfilePics'), $profilePicName);
                $user->profile_pic = $profilePicName;

                $user->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Profile Picture has been Updated',
                'profile_pic' => $user->profile_pic
                    ? url('uploads/ProfilePics/' . $user->profile_pic)
                    : null,
            ]);
        } catch (Exception $e) {
            Log::error('Error updating profile picture: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile picture.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function showsProfilePic(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login first.'
            ], 401);
        }

        $user = Auth::user();

        return response()->json([
            'success' => true,
            'profile_pic_url' => $user->profile_pic
                ? url('uploads/ProfilePics/' . $user->profile_pic)
                : null,
        ]);
    }

    // public function storeOrUpdateForUser(Request $request)
    // {
    //     if (!Auth::check()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Please login first.'
    //         ], 401);
    //     }

    //     try {
    //         $validated = $request->validate([
    //             'first_name' => 'nullable|string|max:255',
    //             'last_name' => 'nullable|string|max:255',
    //             'phone' => 'nullable|string|max:255',
    //             'email' => 'nullable|email|max:255|unique:users,email,' . Auth::id(),
    //             'country' => 'nullable|string|max:255',
    //             'city' => 'nullable|string|max:255',
    //             'road' => 'nullable|string|max:255',
    //             'postal_code' => 'nullable|string|max:255'
    //         ]);

    //         $user = Auth::user();

    //         // Update fields
    //         $user->first_name = $validated['first_name'] ?? $user->first_name;
    //         $user->last_name = $validated['last_name'] ?? $user->last_name;
    //         $user->phone = $validated['phone'] ?? $user->phone;
    //         $user->email = $validated['email'] ?? $user->email;
    //         $user->country = $validated['country'] ?? $user->country;
    //         $user->city = $validated['city'] ?? $user->city;

    //         // Handle profile picture upload
    //         if ($request->hasFile('profile_pic')) {
    //             // Delete old profile picture if exists
    //             if ($user->profile_pic && file_exists(public_path('uploads/ProfilePics/' . $user->profile_pic))) {
    //                 unlink(public_path('uploads/ProfilePics/' . $user->profile_pic));
    //             }

    //             $profilePic = $request->file('profile_pic');
    //             $profilePicName = time() . '_profile.' . $profilePic->getClientOriginalExtension();
    //             $profilePic->move(public_path('uploads/ProfilePics'), $profilePicName);
    //             $user->profile_pic = $profilePicName;

    //             // Alternative using Storage (future-proof):
    //             // $profilePicName = $profilePic->storeAs('profile_pics', $profilePicName, 'public');
    //             // $user->profile_pic = basename($profilePicName);
    //         }

    //         $user->save();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Profile updated successfully.',
    //             'data' => [
    //                 'first_name' => $user->first_name,
    //                 'last_name' => $user->last_name,
    //                 'phone' => $user->phone,
    //                 'email' => $user->email,
    //                 'country' => $user->country,
    //                 'city' => $user->city,
    //                 'profile_pic' => $user->profile_pic
    //                     ? url('uploads/ProfilePics/' . $user->profile_pic)
    //                     : null,
    //             ]
    //         ]);
    //     } catch (\Exception $e) {
    //         Log::error('Error updating user profile: ' . $e->getMessage());

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to update profile.',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function storeOrUpdateForUser(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login first.'
            ], 401);
        }

        try {
            $validated = $request->validate([
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255|unique:users,email,' . Auth::id(),
                'country' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:255',
                'road' => 'nullable|string|max:255',
                'postal_code' => 'nullable|string|max:255'
            ]);

            $user = Auth::user();

            $user->first_name = $validated['first_name'] ?? $user->first_name;
            $user->last_name = $validated['last_name'] ?? $user->last_name;
            $user->phone = $validated['phone'] ?? $user->phone;
            $user->email = $validated['email'] ?? $user->email;
            $user->country = $validated['country'] ?? $user->country;
            $user->city = $validated['city'] ?? $user->city;
            $user->road = $validated['road'] ?? $user->road;
            $user->postal_code = $validated['postal_code'] ?? $user->postal_code;

            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully.',
                'data' => [
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'country' => $user->country,
                    'city' => $user->city,
                    'road' => $user->road,
                    'postal_code' => $user->postal_code,
                ]
            ]);
        } catch (Exception $e) {
            \Log::error('Error updating user profile: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:10248',
        ]);

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $imageName = time() . '_logo.' . $file->getClientOriginalExtension();
            $destinationPath = public_path('uploads/logos');

            // Get existing settings record or create new one
            $setting = Setting::first();

            // Delete old logo file if it exists
            if ($setting && $setting->logo && file_exists(public_path($setting->logo))) {
                unlink(public_path($setting->logo));
            }

            // Move the new file
            $file->move($destinationPath, $imageName);

            // Save to database
            if (!$setting) {
                $setting = Setting::create(['logo' => 'uploads/logos/' . $imageName]);
            } else {
                $setting->update(['logo' => 'uploads/logos/' . $imageName]);
            }

            return back()->with('success', 'Logo updated successfully.');
        }

        return back()->with('error', 'No logo file uploaded.');
    }

    public function updateProfilePic(Request $request)
    {
        $request->validate([
            'profile_pic' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:10248',
        ]);

        if ($request->hasFile('profile_pic')) {
            $file = $request->file('profile_pic');
            $imageName = time() . '_profile.' . $file->getClientOriginalExtension();
            $destinationPath = public_path('uploads/profiles');

            // Get or create settings record
            $setting = Setting::first();

            // Delete old profile picture if it exists
            if ($setting && $setting->profile_pic && file_exists(public_path($setting->profile_pic))) {
                unlink(public_path($setting->profile_pic));
            }

            // Move the new profile picture
            $file->move($destinationPath, $imageName);

            // Save to database
            if (!$setting) {
                $setting = Setting::create(['profile_pic' => 'uploads/profiles/' . $imageName]);
            } else {
                $setting->update(['profile_pic' => 'uploads/profiles/' . $imageName]);
            }

            return back()->with('success', 'Profile picture updated successfully.');
        }

        return back()->with('error', 'No profile picture file uploaded.');
    }
}
