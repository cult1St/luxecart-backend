# E-Commerce Framework

A clean, lightweight, single-vendor e-commerce framework built with pure PHP 8+.

## Features

- **MVC Architecture** - Clean separation of concerns
- **PSR-4 Autoloading** - Modern PHP standards
- **Database Abstraction** - PDO wrapper with query builder
- **Routing System** - Simple and flexible URL routing
- **Request/Response Handling** - Clean HTTP handling
- **Model Layer** - Base model with CRUD operations
- **Authentication** - Built-in user authentication
- **Security** - CSRF protection, input validation, sanitization
- **File Uploads** - Image and file handling
- **Pagination** - Easy pagination
- **Formatting Utilities** - Currency, date, text formatting
- **Error Handling** - Comprehensive error logging

## Directory Structure

```
Frisan/
├── app/
│   ├── Controllers/     # Application controllers
│   ├── Models/          # Database models
│   └── Middleware/      # Request middleware
├── core/
│   ├── Database.php     # Database connection & queries
│   ├── Router.php       # URL routing
│   ├── Request.php      # HTTP request handling
│   └── Response.php     # HTTP response handling
├── config/              # Configuration files
├── helpers/             # Helper functions & classes
├── views/               # HTML templates
├── storage/
│   ├── logs/            # Application logs
│   └── uploads/         # User uploads
├── public/
│   └── index.php        # Application entry point
├── bootstrap.php        # Application bootstrap
└── routes.php           # Route definitions
```

## Quick Start

### 1. Install Dependencies

```bash
composer install
```

### 2. Setup Environment

Copy `.env.example` to `.env` and update configuration:

```bash
cp .env.example .env
```

### 3. Create Database

Run the schema file to create tables:

```sql
-- Import database/schema.php
```

### 4. Start Development Server

```bash
php -S localhost:8000 -t public
```

Visit: http://localhost:8000

## Usage

### Creating a Controller

```php
namespace App\Controllers;

class ProductController extends BaseController
{
    public function index()
    {
        $productModel = new \App\Models\Product($this->db);
        $products = $productModel->all();
        
        $this->response->view('products.index', ['products' => $products]);
    }

    public function show()
    {
        $id = (int)$this->request->input('id');
        $product = new \App\Models\Product($this->db);
        
        if (!$product->find($id)) {
            $this->response->error('Product not found', [], 404);
        }
        
        $this->response->view('products.show', ['product' => $product]);
    }
}
```

### Creating a Model

```php
namespace App\Models;

class Product extends BaseModel
{
    protected string $table = 'products';
    protected array $fillable = ['name', 'description', 'price'];

    public function getActive()
    {
        $sql = "SELECT * FROM products WHERE is_active = 1";
        return $this->db->fetchAll($sql);
    }
}
```

### Defining Routes

```php
// In routes.php
$router->get('/', 'Home', 'index');
$router->get('/products', 'Product', 'index');
$router->post('/products', 'Product', 'store');
$router->get('/product/{id}', 'Product', 'show');
```

### Using Helper Functions

```php
// Generate URLs
echo route('/products'); // /products
echo asset('/css/style.css'); // /assets/css/style.css

// Flash messages
flash('success', 'Product created!');
echo getFlash('success');

// Validation & Sanitization
if (validate('email', $email)) {
    $email = sanitize('email', $email);
}

// Formatting
echo \Helpers\Formatter::currency(99.99);  // $99.99
echo \Helpers\Formatter::slug('Hello World'); // hello-world
```

## Core Classes

### Database

```php
$db = new \Core\Database($config);
$results = $db->fetchAll("SELECT * FROM products WHERE id = ?", [$id]);
$db->insert('products', ['name' => 'Product', 'price' => 99.99]);
$db->update('products', ['name' => 'New Name'], "id = 1");
$db->delete('products', "id = 1");
```

### Request

```php
$request = new \Core\Request();
$request->getMethod(); // GET, POST, etc.
$request->input('key'); // Get input from GET/POST
$request->isPost();
$request->file('upload');
$request->getIp();
```

### Response

```php
$response = new \Core\Response();
$response->json(['key' => 'value']);
$response->success(['data' => 'value']);
$response->error('Error message');
$response->redirect('/products');
$response->view('template', ['data' => 'value']);
```

### Router

```php
$router->get('/path', 'Controller', 'method');
$router->post('/path', 'Controller', 'method');
$router->dispatch($db);
```

## Features in Detail

### Authentication

```php
// In controller
$this->requireAuth(); // Require login
$this->requireAdmin(); // Require admin

// Get user ID
$userId = $this->getUserId();

// Check authentication
if ($this->isAuthenticated()) {
    // User is logged in
}
```

### File Uploads

```php
$fileHandler = new \Helpers\FileHandler();
$path = $fileHandler->upload($_FILES['image'], 'products');
$fileHandler->delete($path);
```

### Pagination

```php
$paginator = new \Helpers\Paginator($items, $page, 15);
$items = $paginator->getItems();
$paginator->hasNextPage();
$paginator->getTotalPages();
```

## Configuration

All configuration is in `config/app.php` and can be overridden with environment variables:

```php
return [
    'app' => [
        'name' => env('APP_NAME'),
        'debug' => env('APP_DEBUG'),
    ],
    'database' => [...],
    'mail' => [...],
    'payment' => [...],
];
```

## Security

- Input validation and sanitization
- CSRF token protection
- Password hashing with bcrypt
- SQL injection prevention via prepared statements
- XSS protection via escaping

## Logging

Application errors are logged to `storage/logs/{date}.log`

## License

MIT

## Support

For issues and questions, please create an issue on the repository.
