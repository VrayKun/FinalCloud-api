<?php

namespace App\Http\Controllers;
use App\Models\Banner;
use App\Models\Product;
use App\Models\Category;
use App\Models\PostTag;
use App\Models\PostCategory;
use App\Models\Post;
use App\Models\Cart;
use App\Models\Brand;
use App\User;
use Auth;
use Session;
use Newsletter;
use DB;
use Hash;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
class FrontendController extends Controller
{
   
    public function index(Request $request){
        return redirect()->route($request->user()->role);
    }

    public function home(){
        $featured=Product::where('status','active')->where('is_featured',1)->orderBy('price','DESC')->limit(2)->get();
        $posts=Post::where('status','active')->orderBy('id','DESC')->limit(3)->get();
        $banners=Banner::where('status','active')->limit(3)->orderBy('id','DESC')->get();
        // return $banner;
        $products=Product::where('status','active')->orderBy('id','DESC')->limit(8)->get();
        $category=Category::where('status','active')->where('is_parent',1)->orderBy('title','ASC')->get();
        // return $category;

        $data = [
            'featured' => $featured,
            'posts' => $posts,
            'banners' => $banners,
            'product_lists' => $products,
            'category_lists' => $category
        ];
        return response()->json($data);
    }   

    public function aboutUs(){
        return view('frontend.pages.about-us');
    }

    public function contact(){
        return view('frontend.pages.contact');
    }

    public function productDetail($slug){
        $product_detail = Product::getProductBySlug($slug);
        return response()->json(['product_detail' => $product_detail]);
    }

    public function productGrids(){
        $products=Product::query();
        
        if(!empty($_GET['category'])){
            $slug=explode(',',$_GET['category']);
            $cat_ids=Category::select('id')->whereIn('slug',$slug)->pluck('id')->toArray();
            $products->whereIn('cat_id',$cat_ids);
        }
        if(!empty($_GET['brand'])){
            $slugs=explode(',',$_GET['brand']);
            $brand_ids=Brand::select('id')->whereIn('slug',$slugs)->pluck('id')->toArray();
            $products->whereIn('brand_id',$brand_ids);
        }
        if(!empty($_GET['sortBy'])){
            if($_GET['sortBy']=='title'){
                $products=$products->where('status','active')->orderBy('title','ASC');
            }
            if($_GET['sortBy']=='price'){
                $products=$products->orderBy('price','ASC');
            }
        }
    
        if(!empty($_GET['price'])){
            $price=explode('-',$_GET['price']);
            $products->whereBetween('price',$price);
        }
    
        $recent_products=Product::where('status','active')->orderBy('id','DESC')->limit(3)->get();
    
        if(!empty($_GET['show'])){
            $products=$products->where('status','active')->paginate($_GET['show']);
        }
        else{
            $products=$products->where('status','active')->paginate(9);
        }
    
        return response()->json([
            'products' => $products,
            'recent_products' => $recent_products
        ]);
    }

    public function productLists(){
        $products=Product::query();
        
        if(!empty($_GET['category'])){
            $slug=explode(',',$_GET['category']);
            $cat_ids=Category::select('id')->whereIn('slug',$slug)->pluck('id')->toArray();
            $products->whereIn('cat_id',$cat_ids);
        }
        if(!empty($_GET['brand'])){
            $slugs=explode(',',$_GET['brand']);
            $brand_ids=Brand::select('id')->whereIn('slug',$slugs)->pluck('id')->toArray();
            $products->whereIn('brand_id',$brand_ids);
        }
        if(!empty($_GET['sortBy'])){
            if($_GET['sortBy']=='title'){
                $products=$products->where('status','active')->orderBy('title','ASC');
            }
            if($_GET['sortBy']=='price'){
                $products=$products->orderBy('price','ASC');
            }
        }
    
        if(!empty($_GET['price'])){
            $price=explode('-',$_GET['price']);
            $products->whereBetween('price',$price);
        }
    
        $recent_products=Product::where('status','active')->orderBy('id','DESC')->limit(3)->get();
    
        if(!empty($_GET['show'])){
            $products=$products->where('status','active')->paginate($_GET['show']);
        }
        else{
            $products=$products->where('status','active')->paginate(6);
        }
    
        return response()->json([
            'products' => $products,
            'recent_products' => $recent_products
        ]);
    }
    
    public function productFilter(Request $request){
        $data= $request->all();
    
        $showURL="";
        if(!empty($data['show'])){
            $showURL .='&show='.$data['show'];
        }
    
        $sortByURL='';
        if(!empty($data['sortBy'])){
            $sortByURL .='&sortBy='.$data['sortBy'];
        }
    
        $catURL="";
        if(!empty($data['category'])){
            foreach($data['category'] as $category){
                if(empty($catURL)){
                    $catURL .='&category='.$category;
                }
                else{
                    $catURL .=','.$category;
                }
            }
        }
    
        $brandURL="";
        if(!empty($data['brand'])){
            foreach($data['brand'] as $brand){
                if(empty($brandURL)){
                    $brandURL .='&brand='.$brand;
                }
                else{
                    $brandURL .=','.$brand;
                }
            }
        }
    
        $priceRangeURL="";
        if(!empty($data['price_range'])){
            $priceRangeURL .='&price='.$data['price_range'];
        }
    
        $filterURL = $catURL.$brandURL.$priceRangeURL.$showURL.$sortByURL;
    
        return response()->json(['filterURL' => $filterURL]);
    }
    public function productSearch(Request $request){
    $recent_products=Product::where('status','active')->orderBy('id','DESC')->limit(3)->get();
    $products=Product::orwhere('title','like','%'.$request->search.'%')
                ->orwhere('slug','like','%'.$request->search.'%')
                ->orwhere('description','like','%'.$request->search.'%')
                ->orwhere('summary','like','%'.$request->search.'%')
                ->orwhere('price','like','%'.$request->search.'%')
                ->orderBy('id','DESC')
                ->paginate('9');
    return response()->json([
        'products' => $products,
        'recent_products' => $recent_products
    ]);
}

    public function productBrand(Request $request){
        $products=Brand::getProductByBrand($request->slug);
        $recent_products=Product::where('status','active')->orderBy('id','DESC')->limit(3)->get();
        return response()->json([
            'products' => $products->products,
            'recent_products' => $recent_products
        ]);
    }

    public function productCat(Request $request){
        $products=Category::getProductByCat($request->slug);
        $recent_products=Product::where('status','active')->orderBy('id','DESC')->limit(3)->get();
        return response()->json([
            'products' => $products->products,
            'recent_products' => $recent_products
        ]);
    }

    public function productSubCat(Request $request){
        $products=Category::getProductBySubCat($request->sub_slug);
        $recent_products=Product::where('status','active')->orderBy('id','DESC')->limit(3)->get();
        return response()->json([
            'products' => $products->sub_products,
            'recent_products' => $recent_products
        ]);
    }

    public function blog(){
        $post=Post::query();
        
        if(!empty($_GET['category'])){
            $slug=explode(',',$_GET['category']);
            $cat_ids=PostCategory::select('id')->whereIn('slug',$slug)->pluck('id')->toArray();
            $post->whereIn('post_cat_id',$cat_ids);
        }
        if(!empty($_GET['tag'])){
            $slug=explode(',',$_GET['tag']);
            $tag_ids=PostTag::select('id')->whereIn('slug',$slug)->pluck('id')->toArray();
            $post->where('post_tag_id',$tag_ids);
        }
    
        if(!empty($_GET['show'])){
            $post=$post->where('status','active')->orderBy('id','DESC')->paginate($_GET['show']);
        }
        else{
            $post=$post->where('status','active')->orderBy('id','DESC')->paginate(9);
        }
    
        $rcnt_post=Post::where('status','active')->orderBy('id','DESC')->limit(3)->get();
    
        return response()->json([
            'posts' => $post,
            'recent_posts' => $rcnt_post
        ]);
    }

    public function blogDetail($slug){
        $post=Post::getPostBySlug($slug);
        $rcnt_post=Post::where('status','active')->orderBy('id','DESC')->limit(3)->get();
        // return $post;
        return response()->json([
            'post' => $post,
            'recent_posts' => $rcnt_post
        ]);
    }

    public function blogSearch(Request $request){
        // return $request->all();
        $rcnt_post=Post::where('status','active')->orderBy('id','DESC')->limit(3)->get();
        $posts=Post::orwhere('title','like','%'.$request->search.'%')
            ->orwhere('quote','like','%'.$request->search.'%')
            ->orwhere('summary','like','%'.$request->search.'%')
            ->orwhere('description','like','%'.$request->search.'%')
            ->orwhere('slug','like','%'.$request->search.'%')
            ->orderBy('id','DESC')
            ->paginate(8);
        return response()->json([
            'posts' => $posts,
            'recent_posts' => $rcnt_post
        ]);
    }

    public function blogFilter(Request $request){
        $data=$request->all();
        // return $data;
        $catURL="";
        if(!empty($data['category'])){
            foreach($data['category'] as $category){
                if(empty($catURL)){
                    $catURL .='&category='.$category;
                }
                else{
                    $catURL .=','.$category;
                }
            }
        }

        $tagURL="";
        if(!empty($data['tag'])){
            foreach($data['tag'] as $tag){
                if(empty($tagURL)){
                    $tagURL .='&tag='.$tag;
                }
                else{
                    $tagURL .=','.$tag;
                }
            }
        }
        // return $tagURL;
            // return $catURL;
        return response()->json(['filterURL' => $catURL.$tagURL]);
    }

    public function blogByCategory(Request $request){
        $post=PostCategory::getBlogByCategory($request->slug);
        $rcnt_post=Post::where('status','active')->orderBy('id','DESC')->limit(3)->get();
        return response()->json([
            'posts' => $post->post,
            'recent_posts' => $rcnt_post
        ]);
    }

    public function blogByTag(Request $request){
        // dd($request->slug);
        $post=Post::getBlogByTag($request->slug);
        // return $post;
        $rcnt_post=Post::where('status','active')->orderBy('id','DESC')->limit(3)->get();
        // return view('frontend.pages.blog')->with('posts',$post)->with('recent_posts',$rcnt_post);
        return response()->json([
            'posts' => $post,
            'recent_posts' => $rcnt_post
        ]);
    }

    // Login
    public function login(){
        return view('frontend.pages.login');
    }
    public function loginSubmit(Request $request){
        $data= $request->all();
        if(Auth::attempt(['email' => $data['email'], 'password' => $data['password'],'status'=>'active'])){
            $user = Auth::user();
            Session::put('user',$data['email']);
            return response()->json([
                'message' => 'Logged in successfully!',
                'status' => 200,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ]
            ]);
        }
        else{
            return response()->json([
                'message' => 'Invalid email and password please try again!',
                'status' => 401
            ]);
        }
    }


    public function logout(){
        Session::forget('user');
        Auth::logout();
        return response()->json([
            'message' => 'Logged out successfully',
            'status' => 200
        ]);
    }

    public function register(){
        return view('frontend.pages.register');
    }
    public function registerSubmit(Request $request){
        $this->validate($request,[
            'name'=>'string|required|min:2',
            'email'=>'string|required|unique:users,email',
            'password'=>'required|min:6|confirmed',
        ]);
        $data=$request->all();
        $check=$this->create($data);
        Session::put('user',$data['email']);
        if($check){
            return response()->json([
                'message' => 'Registered successfully',
                'status' => 200
            ]);
        }
        else{
            return response()->json([
                'message' => 'Please try again!',
                'status' => 500
            ]);
        }
    }
    public function create(Request $request){
        $data = $request->all();
        $user = User::create([
            'name'=>$data['name'],
            'email'=>$data['email'],
            'password'=>Hash::make($data['password']),
            'status'=>'active'
        ]);
    
        if($user){
            return response()->json([
                'message' => 'User created successfully',
                'status' => 200
            ]);
        }
        else{
            return response()->json([
                'message' => 'Failed to create user',
                'status' => 500
            ]);
        }
    }
    // Reset password
    public function showResetForm(){
        return view('auth.passwords.old-reset');
    }

    public function subscribe(Request $request){
        if(! Newsletter::isSubscribed($request->email)){
                Newsletter::subscribePending($request->email);
                if(Newsletter::lastActionSucceeded()){
                    request()->session()->flash('success','Subscribed! Please check your email');
                    return redirect()->route('home');
                }
                else{
                    Newsletter::getLastError();
                    return back()->with('error','Something went wrong! please try again');
                }
            }
            else{
                request()->session()->flash('error','Already Subscribed');
                return back();
            }
    }
    
}