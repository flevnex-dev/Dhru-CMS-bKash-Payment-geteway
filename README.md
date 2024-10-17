# bKash Payment Gateway Integration for Dhru CMS

**Overview**  
This project integrates bKash payment gateway into Dhru CMS, enabling seamless transaction processing for customers. 

**Features**  
- Easy integration with bKash payment services
- Secure and reliable payment processing
- Simple interface for user-friendly experience

**Requirements**  
- PHP 7.x or higher
- Laravel 8.x or higher
- MySQL
- Composer

**Installation**  
1. Clone the repository:  
   `git clone https://github.com/flevnex-dev/Dhru-CMS-bKash-Payment-geteway.git`
2. Navigate to the project directory:  
   `cd Dhru-CMS-bKash-Payment-geteway`
3. Install dependencies:  
   `composer install`
4. Copy `.env` file:  
   `cp .env.example .env`
5. Update `.env` with your bKash credentials:

**Configuration**
- **bKash API Credentials**  
  - `BKASH_API_KEY=your_api_key`
  - `BKASH_API_SECRET=your_api_secret`
  - `BKASH_ENVIRONMENT=sandbox`  # or 'live' for production
  - `BKASH_SUCCESS_URL=http://yourdomain.com/payment/success`
  - `BKASH_FAILURE_URL=http://yourdomain.com/payment/failure`
  - `BKASH_LOG_LEVEL=debug`

**Usage**  
1. Follow the bKash API documentation for transaction handling.
2. Implement the necessary routes within your Laravel application.

**Contributing**  
Feel free to submit a pull request or open an issue if you'd like to contribute.

**License**  
This project is licensed under the MIT License.

**Contact Us**  
For further queries or assistance, please contact us at:  
contact@flevnex.com
