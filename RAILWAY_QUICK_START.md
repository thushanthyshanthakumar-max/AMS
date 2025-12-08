# Quick Railway Deployment Checklist

## ✅ Files Created
- ✅ `railway.json` - Railway configuration
- ✅ `nixpacks.toml` - PHP environment setup
- ✅ `includes/config.railway.php` - Production database config
- ✅ `.gitignore` - Git ignore rules
- ✅ `RAILWAY_DEPLOYMENT.md` - Full deployment guide

## 📋 Deployment Steps

### 1. Push to GitHub
```bash
cd c:\xampp\htdocs\AMS
git init
git add .
git commit -m "Initial commit - Ready for Railway deployment"
```

Create a new repository on GitHub, then:
```bash
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO.git
git branch -M main
git push -u origin main
```

### 2. Deploy to Railway

**Option A: Web Dashboard (Easiest)**
1. Go to [railway.app](https://railway.app) and login with GitHub
2. Click "New Project" → "Deploy from GitHub repo"
3. Select your repository
4. Click "+ New" → "Database" → "Add MySQL"
5. Wait for deployment to complete
6. Click "Settings" → "Generate Domain" to get your URL

**Option B: CLI**
```bash
npm i -g @railway/cli
railway login
railway init
railway add --database mysql
railway up
```

### 3. Import Database
```bash
# Option 1: Using Railway CLI
railway connect mysql
# Then in MySQL prompt:
source database_setup.sql;

# Option 2: Get credentials from Railway dashboard and use MySQL client
# Go to MySQL service → Connect → Copy credentials
mysql -h [HOST] -P [PORT] -u [USER] -p [DATABASE] < database_setup.sql
```

### 4. Access Your App
- Your app will be at: `https://your-app.railway.app`
- Login with your admin credentials
- Test all features

### 5. Security (Important!)
After first deployment:
- Delete or protect `setup.php`
- Verify database connection works
- Test admin and teacher logins

## 🔧 Environment Variables (Auto-set by Railway)
Railway automatically sets these when you add MySQL:
- `MYSQLHOST`
- `MYSQLDATABASE`
- `MYSQLUSER`
- `MYSQLPASSWORD`
- `MYSQLPORT`
- `RAILWAY_ENVIRONMENT`

Your app automatically detects these and uses `config.railway.php`!

## 💰 Pricing
- **Free**: $5 credit/month
- **Typical cost**: $5-15/month for small apps
- **Billing**: Usage-based after free tier

## 🆘 Troubleshooting

**Database connection failed?**
- Check MySQL service is running in Railway dashboard
- Verify environment variables are set
- Check deployment logs

**500 Error?**
- View logs: Railway dashboard → Deployments → View Logs
- Or use CLI: `railway logs`

**CSS/JS not loading?**
- Check file paths are relative
- Verify files are in repository

## 📚 Resources
- Full guide: `RAILWAY_DEPLOYMENT.md`
- Railway docs: [docs.railway.app](https://docs.railway.app)
- Support: Railway Discord community

## 🔄 Updating Your App
After making changes:
```bash
git add .
git commit -m "Your changes"
git push origin main
```
Railway auto-deploys on push!
