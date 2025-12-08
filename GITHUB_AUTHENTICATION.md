# GitHub Authentication Guide

## Creating a Personal Access Token (PAT)

Since you're getting "Repository not found" error, you need to authenticate with GitHub.

### Step 1: Create Personal Access Token

1. **Go to GitHub Settings**:
   - Visit: https://github.com/settings/tokens
   - Or: Click your profile picture → Settings → Developer settings → Personal access tokens → Tokens (classic)

2. **Generate New Token**:
   - Click "Generate new token" → "Generate new token (classic)"
   - Note: "Token for AMS deployment"
   - Expiration: Choose your preference (90 days recommended)
   - **Select scopes**: Check the `repo` checkbox (this gives full control of private repositories)
   
3. **Generate and Copy**:
   - Click "Generate token" at the bottom
   - **IMPORTANT**: Copy the token immediately (you won't see it again!)
   - It looks like: `ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`

### Step 2: Push Using the Token

Once you have your token, use ONE of these methods:

#### Method A: Push with Token in URL (One-time)
```bash
git push https://YOUR_TOKEN@github.com/thushanthyshanthakumar-max/ams.git main
```

Replace `YOUR_TOKEN` with the token you copied.

#### Method B: Configure Git Credential Manager (Permanent)

Windows should prompt you for credentials. When it does:
- Username: `thushanthyshanthakumar-max`
- Password: Paste your Personal Access Token (NOT your GitHub password)

Then run:
```bash
git push -u origin main
```

#### Method C: Store Credentials (Alternative)
```bash
git config --global credential.helper wincred
git push -u origin main
```
When prompted, enter your token as the password.

### Step 3: Verify Push Success

After successful push, you should see:
```
Enumerating objects: X, done.
Counting objects: 100% (X/X), done.
...
To https://github.com/thushanthyshanthakumar-max/ams.git
 * [new branch]      main -> main
Branch 'main' set up to track remote branch 'main' from 'origin'.
```

### Troubleshooting

**"Repository not found" error?**
- This usually means authentication failed
- Make sure you're using the token, not your password
- Verify the repository exists at: https://github.com/thushanthyshanthakumar-max/ams

**Token not working?**
- Make sure you selected the `repo` scope when creating the token
- Check the token hasn't expired
- Verify you copied the entire token

**Still having issues?**
- Try Method A (push with token in URL) first - it's the most reliable
- Make sure you're logged into GitHub in your browser

### Security Note

⚠️ **Never commit your token to the repository!**
- Don't add it to any files
- Don't share it publicly
- If exposed, delete it and create a new one

### Alternative: Install GitHub CLI (Optional for Future)

For easier authentication in the future:
```bash
winget install GitHub.cli
# Then run:
gh auth login
```

This makes future pushes much easier!
