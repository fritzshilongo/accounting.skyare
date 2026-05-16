# Skyare Subdomain Onboarding & DNS Setup

## 1. DNS Wildcard Setup
- Add a DNS A record for `*.skyare.space` pointing to your server’s IP address.
- This allows any subdomain (client1.skyare.space, client2.skyare.space, etc.) to resolve to your app.

## 2. Registration Flow
- Direct new clients to `/register-company` on your main domain.
- They enter company name, admin email, and desired subdomain.
- The system checks subdomain uniqueness, creates the company, admin user, and a 14-day trial license.
- User is redirected to `https://subdomain.skyare.space/login` to log in.

## 3. Company & License Separation
- Each subdomain is mapped to a unique company in the database.
- Each company has its own users, data, and license.

## 4. Admin Actions
- You can view and manage companies and licenses directly in the database (or extend with an admin UI).
- To issue a paid license, insert a new row in the `licenses` table for the company and subdomain.

## 5. Security Notes
- The registration form enforces subdomain uniqueness and basic validation.
- Ensure your web server (nginx/apache) is configured to accept all subdomains (server_name *.skyare.space;).

---

This flow enables true multi-tenant SaaS onboarding with isolated data and licensing per client subdomain.
