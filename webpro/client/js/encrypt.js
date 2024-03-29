/* The following file is provided by http://pajhome.org.uk/crypt/md5/.
 * The functions are licensed under the BSD license, and have been modified to be used in a container. */

/* The following file is provided by http://pajhome.org.uk/crypt/md5/.
 * The functions are licensed under the BSD license, and have been modified to be used in a container. */

md5 = {
  /*
   * A JavaScript implementation of the RSA Data Security, Inc. MD5 Message
   * Digest Algorithm, as defined in RFC 1321.
   * Version 2.2 Copyright (C) Paul Johnston 1999 - 2009
   * Other contributors: Greg Holt, Andrew Kepert, Ydnar, Lostinet
   * Distributed under the BSD License
   * See http://pajhome.org.uk/crypt/md5 for more info.
   */

  /*
   * Configurable variables. You may need to tweak these to be compatible with
   * the server-side, but the defaults work in most cases.
   */
  hexcase : 0,   /* hex output format. 0 - lowercase; 1 - uppercase        */
  b64pad  : "",  /* base-64 pad character. "=" for strict RFC compliance   */

  /*
   * These are the functions you'll usually want to call
   * They take string arguments and return either hex or base-64 encoded strings
   */
  hex_md5 : function(s) { return md5.rstr2hex(md5.rstr_md5(md5.str2rstr_utf8(s))); },
  b64_md5 : function (s)    { return md5.rstr2b64(md5.rstr_md5(md5.str2rstr_utf8(s))); },
  any_md5 : function(s, e) { return md5.rstr2any(md5.rstr_md5(md5.str2rstr_utf8(s)), e); },
  hex_hmac_md5 : function(k, d) { return md5.rstr2hex(md5.rstr_hmac_md5(md5.str2rstr_utf8(k), md5.str2rstr_utf8(d))); },
  b64_hmac_md5 : function(k, d) { return md5.rstr2b64(md5.rstr_hmac_md5(md5.str2rstr_utf8(k), md5.str2rstr_utf8(d))); },
  any_hmac_md5 : function(k, d, e) { return md5.rstr2any(md5.rstr_hmac_md5(md5.str2rstr_utf8(k), md5.str2rstr_utf8(d)), e); },

  /*
   * Perform a simple self-test to see if the VM is working
   */
  md5_vm_test : function() { return md5.hex_md5("abc").toLowerCase() == "900150983cd24fb0d6963f7d28e17f72"; },

  /*
   * Calculate the MD5 of a raw string
   */
  rstr_md5 : function(s) { return md5.binl2rstr(md5.binl_md5(md5.rstr2binl(s), s.length * 8)); },

  /*
   * Calculate the HMAC-MD5, of a key and some data (raw strings)
   */
  rstr_hmac_md5 : function(key, data) {
    var bkey = md5.rstr2binl(key);
    if(bkey.length > 16) bkey = md5.binl_md5(bkey, key.length * 8);

    var ipad = Array(16), opad = Array(16);
    for(var i = 0; i < 16; i++)
    {
      ipad[i] = bkey[i] ^ 0x36363636;
      opad[i] = bkey[i] ^ 0x5C5C5C5C;
    }

    var hash = md5.binl_md5(ipad.concat(md5.rstr2binl(data)), 512 + data.length * 8);
    return md5.binl2rstr(md5.binl_md5(opad.concat(hash), 512 + 128));
  },

  /*
   * Convert a raw string to a hex string
   */
  rstr2hex : function(input) {
    try { hexcase } catch(e) { hexcase=0; }
    var hex_tab = hexcase ? "0123456789ABCDEF" : "0123456789abcdef";
    var output = "";
    var x;
    for(var i = 0; i < input.length; i++)
    {
      x = input.charCodeAt(i);
      output += hex_tab.charAt((x >>> 4) & 0x0F)
             +  hex_tab.charAt( x        & 0x0F);
    }
    return output;
  },

  /*
   * Convert a raw string to a base-64 string
   */
  rstr2b64 : function(input) {
    try { b64pad } catch(e) { b64pad=''; }
    var tab = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
    var output = "";
    var len = input.length;
    for(var i = 0; i < len; i += 3)
    {
      var triplet = (input.charCodeAt(i) << 16)
                  | (i + 1 < len ? input.charCodeAt(i+1) << 8 : 0)
                  | (i + 2 < len ? input.charCodeAt(i+2)      : 0);
      for(var j = 0; j < 4; j++)
      {
        if(i * 8 + j * 6 > input.length * 8) output += b64pad;
        else output += tab.charAt((triplet >>> 6*(3-j)) & 0x3F);
      }
    }
    return output;
  },

  /*
   * Convert a raw string to an arbitrary string encoding
   */
  rstr2any : function(input, encoding) {
    var divisor = encoding.length;
    var i, j, q, x, quotient;

    /* Convert to an array of 16-bit big-endian values, forming the dividend */
    var dividend = Array(Math.ceil(input.length / 2));
    for(i = 0; i < dividend.length; i++)
    {
      dividend[i] = (input.charCodeAt(i * 2) << 8) | input.charCodeAt(i * 2 + 1);
    }

    /*
     * Repeatedly perform a long division. The binary array forms the dividend,
     * the length of the encoding is the divisor. Once computed, the quotient
     * forms the dividend for the next step. All remainders are stored for later
     * use.
     */
    var full_length = Math.ceil(input.length * 8 / (Math.log(encoding.length) / Math.log(2)));
    var remainders = Array(full_length);
    for(j = 0; j < full_length; j++) {
      quotient = Array();
      x = 0;
      for(i = 0; i < dividend.length; i++) {
        x = (x << 16) + dividend[i];
        q = Math.floor(x / divisor);
        x -= q * divisor;
        if(quotient.length > 0 || q > 0)
          quotient[quotient.length] = q;
      }
      remainders[j] = x;
      dividend = quotient;
    }

    /* Convert the remainders to the output string */
    var output = "";
    for(i = remainders.length - 1; i >= 0; i--)
      output += encoding.charAt(remainders[i]);

    return output;
  },

  /*
   * Encode a string as utf-8.
   * For efficiency, this assumes the input is valid utf-16.
   */
  str2rstr_utf8 : function(input) {
    var output = "";
    var i = -1;
    var x, y;

    while(++i < input.length)
    {
      /* Decode utf-16 surrogate pairs */
      x = input.charCodeAt(i);
      y = i + 1 < input.length ? input.charCodeAt(i + 1) : 0;
      if(0xD800 <= x && x <= 0xDBFF && 0xDC00 <= y && y <= 0xDFFF)
      {
        x = 0x10000 + ((x & 0x03FF) << 10) + (y & 0x03FF);
        i++;
      }

      /* Encode output as utf-8 */
      if(x <= 0x7F)
        output += String.fromCharCode(x);
      else if(x <= 0x7FF)
        output += String.fromCharCode(0xC0 | ((x >>> 6 ) & 0x1F),
                                      0x80 | ( x         & 0x3F));
      else if(x <= 0xFFFF)
        output += String.fromCharCode(0xE0 | ((x >>> 12) & 0x0F),
                                      0x80 | ((x >>> 6 ) & 0x3F),
                                      0x80 | ( x         & 0x3F));
      else if(x <= 0x1FFFFF)
        output += String.fromCharCode(0xF0 | ((x >>> 18) & 0x07),
                                      0x80 | ((x >>> 12) & 0x3F),
                                      0x80 | ((x >>> 6 ) & 0x3F),
                                      0x80 | ( x         & 0x3F));
    }
    return output;
  },

  /*
   * Encode a string as utf-16
   */
  str2rstr_utf16le : function(input) {
    var output = "";
    for(var i = 0; i < input.length; i++)
      output += String.fromCharCode( input.charCodeAt(i)        & 0xFF,
                                    (input.charCodeAt(i) >>> 8) & 0xFF);
    return output;
  },

  str2rstr_utf16be : function(input) {
    var output = "";
    for(var i = 0; i < input.length; i++)
      output += String.fromCharCode((input.charCodeAt(i) >>> 8) & 0xFF,
                                     input.charCodeAt(i)        & 0xFF);
    return output;
  },

  /*
   * Convert a raw string to an array of little-endian words
   * Characters >255 have their high-byte silently ignored.
   */
  rstr2binl : function(input) {
    var output = Array(input.length >> 2);
    for(var i = 0; i < output.length; i++)
      output[i] = 0;
    for(var i = 0; i < input.length * 8; i += 8)
      output[i>>5] |= (input.charCodeAt(i / 8) & 0xFF) << (i%32);
    return output;
  },

  /*
   * Convert an array of little-endian words to a string
   */
  binl2rstr : function(input) {
    var output = "";
    for(var i = 0; i < input.length * 32; i += 8)
      output += String.fromCharCode((input[i>>5] >>> (i % 32)) & 0xFF);
    return output;
  },

  /*
   * Calculate the MD5 of an array of little-endian words, and a bit length.
   */
  binl_md5 : function(x, len) {
    /* append padding */
    x[len >> 5] |= 0x80 << ((len) % 32);
    x[(((len + 64) >>> 9) << 4) + 14] = len;

    var a =  1732584193;
    var b = -271733879;
    var c = -1732584194;
    var d =  271733878;

    for(var i = 0; i < x.length; i += 16) {
      var olda = a;
      var oldb = b;
      var oldc = c;
      var oldd = d;

      a = md5.md5_ff(a, b, c, d, x[i+ 0], 7 , -680876936);
      d = md5.md5_ff(d, a, b, c, x[i+ 1], 12, -389564586);
      c = md5.md5_ff(c, d, a, b, x[i+ 2], 17,  606105819);
      b = md5.md5_ff(b, c, d, a, x[i+ 3], 22, -1044525330);
      a = md5.md5_ff(a, b, c, d, x[i+ 4], 7 , -176418897);
      d = md5.md5_ff(d, a, b, c, x[i+ 5], 12,  1200080426);
      c = md5.md5_ff(c, d, a, b, x[i+ 6], 17, -1473231341);
      b = md5.md5_ff(b, c, d, a, x[i+ 7], 22, -45705983);
      a = md5.md5_ff(a, b, c, d, x[i+ 8], 7 ,  1770035416);
      d = md5.md5_ff(d, a, b, c, x[i+ 9], 12, -1958414417);
      c = md5.md5_ff(c, d, a, b, x[i+10], 17, -42063);
      b = md5.md5_ff(b, c, d, a, x[i+11], 22, -1990404162);
      a = md5.md5_ff(a, b, c, d, x[i+12], 7 ,  1804603682);
      d = md5.md5_ff(d, a, b, c, x[i+13], 12, -40341101);
      c = md5.md5_ff(c, d, a, b, x[i+14], 17, -1502002290);
      b = md5.md5_ff(b, c, d, a, x[i+15], 22,  1236535329);

      a = md5.md5_gg(a, b, c, d, x[i+ 1], 5 , -165796510);
      d = md5.md5_gg(d, a, b, c, x[i+ 6], 9 , -1069501632);
      c = md5.md5_gg(c, d, a, b, x[i+11], 14,  643717713);
      b = md5.md5_gg(b, c, d, a, x[i+ 0], 20, -373897302);
      a = md5.md5_gg(a, b, c, d, x[i+ 5], 5 , -701558691);
      d = md5.md5_gg(d, a, b, c, x[i+10], 9 ,  38016083);
      c = md5.md5_gg(c, d, a, b, x[i+15], 14, -660478335);
      b = md5.md5_gg(b, c, d, a, x[i+ 4], 20, -405537848);
      a = md5.md5_gg(a, b, c, d, x[i+ 9], 5 ,  568446438);
      d = md5.md5_gg(d, a, b, c, x[i+14], 9 , -1019803690);
      c = md5.md5_gg(c, d, a, b, x[i+ 3], 14, -187363961);
      b = md5.md5_gg(b, c, d, a, x[i+ 8], 20,  1163531501);
      a = md5.md5_gg(a, b, c, d, x[i+13], 5 , -1444681467);
      d = md5.md5_gg(d, a, b, c, x[i+ 2], 9 , -51403784);
      c = md5.md5_gg(c, d, a, b, x[i+ 7], 14,  1735328473);
      b = md5.md5_gg(b, c, d, a, x[i+12], 20, -1926607734);

      a = md5.md5_hh(a, b, c, d, x[i+ 5], 4 , -378558);
      d = md5.md5_hh(d, a, b, c, x[i+ 8], 11, -2022574463);
      c = md5.md5_hh(c, d, a, b, x[i+11], 16,  1839030562);
      b = md5.md5_hh(b, c, d, a, x[i+14], 23, -35309556);
      a = md5.md5_hh(a, b, c, d, x[i+ 1], 4 , -1530992060);
      d = md5.md5_hh(d, a, b, c, x[i+ 4], 11,  1272893353);
      c = md5.md5_hh(c, d, a, b, x[i+ 7], 16, -155497632);
      b = md5.md5_hh(b, c, d, a, x[i+10], 23, -1094730640);
      a = md5.md5_hh(a, b, c, d, x[i+13], 4 ,  681279174);
      d = md5.md5_hh(d, a, b, c, x[i+ 0], 11, -358537222);
      c = md5.md5_hh(c, d, a, b, x[i+ 3], 16, -722521979);
      b = md5.md5_hh(b, c, d, a, x[i+ 6], 23,  76029189);
      a = md5.md5_hh(a, b, c, d, x[i+ 9], 4 , -640364487);
      d = md5.md5_hh(d, a, b, c, x[i+12], 11, -421815835);
      c = md5.md5_hh(c, d, a, b, x[i+15], 16,  530742520);
      b = md5.md5_hh(b, c, d, a, x[i+ 2], 23, -995338651);

      a = md5.md5_ii(a, b, c, d, x[i+ 0], 6 , -198630844);
      d = md5.md5_ii(d, a, b, c, x[i+ 7], 10,  1126891415);
      c = md5.md5_ii(c, d, a, b, x[i+14], 15, -1416354905);
      b = md5.md5_ii(b, c, d, a, x[i+ 5], 21, -57434055);
      a = md5.md5_ii(a, b, c, d, x[i+12], 6 ,  1700485571);
      d = md5.md5_ii(d, a, b, c, x[i+ 3], 10, -1894986606);
      c = md5.md5_ii(c, d, a, b, x[i+10], 15, -1051523);
      b = md5.md5_ii(b, c, d, a, x[i+ 1], 21, -2054922799);
      a = md5.md5_ii(a, b, c, d, x[i+ 8], 6 ,  1873313359);
      d = md5.md5_ii(d, a, b, c, x[i+15], 10, -30611744);
      c = md5.md5_ii(c, d, a, b, x[i+ 6], 15, -1560198380);
      b = md5.md5_ii(b, c, d, a, x[i+13], 21,  1309151649);
      a = md5.md5_ii(a, b, c, d, x[i+ 4], 6 , -145523070);
      d = md5.md5_ii(d, a, b, c, x[i+11], 10, -1120210379);
      c = md5.md5_ii(c, d, a, b, x[i+ 2], 15,  718787259);
      b = md5.md5_ii(b, c, d, a, x[i+ 9], 21, -343485551);

      a = md5.safe_add(a, olda);
      b = md5.safe_add(b, oldb);
      c = md5.safe_add(c, oldc);
      d = md5.safe_add(d, oldd);
    }
    return Array(a, b, c, d);
  },

  /*
   * These functions implement the four basic operations the algorithm uses.
   */
  md5_cmn : function(q, a, b, x, s, t) { return md5.safe_add(md5.bit_rol(md5.safe_add(md5.safe_add(a, q), md5.safe_add(x, t)), s),b); },
  md5_ff : function(a, b, c, d, x, s, t) { return md5.md5_cmn((b & c) | ((~b) & d), a, b, x, s, t); },
  md5_gg : function(a, b, c, d, x, s, t) { return md5.md5_cmn((b & d) | (c & (~d)), a, b, x, s, t); },
  md5_hh : function(a, b, c, d, x, s, t) { return md5.md5_cmn(b ^ c ^ d, a, b, x, s, t); },
  md5_ii : function(a, b, c, d, x, s, t) { return md5.md5_cmn(c ^ (b | (~d)), a, b, x, s, t); },

  /*
   * Add integers, wrapping at 2^32. This uses 16-bit operations internally
   * to work around bugs in some JS interpreters.
   */
  safe_add : function(x, y) {
    var lsw = (x & 0xFFFF) + (y & 0xFFFF);
    var msw = (x >> 16) + (y >> 16) + (lsw >> 16);
    return (msw << 16) | (lsw & 0xFFFF);
  },

  /*
   * Bitwise rotate a 32-bit number to the left.
   */
  bit_rol : function(num, cnt) { return (num << cnt) | (num >>> (32 - cnt)); }
}





/*
 * A JavaScript implementation of the Secure Hash Algorithm, SHA-256, as defined
 * in FIPS 180-2
 * Version 2.2 Copyright Angel Marin, Paul Johnston 2000 - 2009.
 * Other contributors: Greg Holt, Andrew Kepert, Ydnar, Lostinet
 * Distributed under the BSD License
 * See http://pajhome.org.uk/crypt/md5 for details.
 * Also http://anmar.eu.org/projects/jssha2/
 */

sha256 = {
  /*
   * Configurable variables. You may need to tweak these to be compatible with
   * the server-side, but the defaults work in most cases.
   */
  hexcase : 0,  /* hex output format. 0 - lowercase; 1 - uppercase        */
  b64pad  : "", /* base-64 pad character. "=" for strict RFC compliance   */

  /*
   * These are the functions you'll usually want to call
   * They take string arguments and return either hex or base-64 encoded strings
   */
  hex_sha256 : function(s) { return sha256.rstr2hex(sha256.rstr_sha256(sha256.str2rstr_utf8(s))); },
  b64_sha256 : function(s) { return sha256.rstr2b64(sha256.rstr_sha256(sha256.str2rstr_utf8(s))); },
  any_sha256 : function(s, e) { return sha256.rstr2any(sha256.rstr_sha256(sha256.str2rstr_utf8(s)), e); },
  hex_hmac_sha256 : function(k, d) { return sha256.rstr2hex(sha256.rstr_hmac_sha256(sha256.str2rstr_utf8(k), sha256.str2rstr_utf8(d))); },
  b64_hmac_sha256 : function(k, d) { return sha256.rstr2b64(sha256.rstr_hmac_sha256(sha256.str2rstr_utf8(k), sha256.str2rstr_utf8(d))); },
  any_hmac_sha256 : function(k, d, e) { return sha256.rstr2any(sha256.rstr_hmac_sha256(sha256.str2rstr_utf8(k), sha256.str2rstr_utf8(d)), e); },

  /*
   * Perform a simple self-test to see if the VM is working
   */
  sha256_vm_test : function() { return sha256.hex_sha256("abc").toLowerCase() == "ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad"; },

  /*
   * Calculate the sha256 of a raw string
   */
  rstr_sha256 : function(s) {
    return sha256.binb2rstr(sha256.binb_sha256(sha256.rstr2binb(s), s.length * 8));
  },

  /*
   * Calculate the HMAC-sha256 of a key and some data (raw strings)
   */
  rstr_hmac_sha256 : function(key, data) {
    var bkey = sha256.rstr2binb(key);
    if(bkey.length > 16) bkey = sha256.binb_sha256(bkey, key.length * 8);

    var ipad = Array(16), opad = Array(16);
    for(var i = 0; i < 16; i++)
    {
      ipad[i] = bkey[i] ^ 0x36363636;
      opad[i] = bkey[i] ^ 0x5C5C5C5C;
    }

    var hash = sha256.binb_sha256(ipad.concat(sha256.rstr2binb(data)), 512 + data.length * 8);
    return sha256.binb2rstr(sha256.binb_sha256(opad.concat(hash), 512 + 256));
  },

  /*
   * Convert a raw string to a hex string
   */
  rstr2hex : function(input) {
    try { hexcase } catch(e) { hexcase=0; }
    var hex_tab = hexcase ? "0123456789ABCDEF" : "0123456789abcdef";
    var output = "";
    var x;
    for(var i = 0; i < input.length; i++)
    {
      x = input.charCodeAt(i);
      output += hex_tab.charAt((x >>> 4) & 0x0F)
             +  hex_tab.charAt( x        & 0x0F);
    }
    return output;
  },

  /*
   * Convert a raw string to a base-64 string
   */
  rstr2b64 : function(input) {
    try { b64pad } catch(e) { b64pad=''; }
    var tab = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
    var output = "";
    var len = input.length;
    for(var i = 0; i < len; i += 3)
    {
      var triplet = (input.charCodeAt(i) << 16)
                  | (i + 1 < len ? input.charCodeAt(i+1) << 8 : 0)
                  | (i + 2 < len ? input.charCodeAt(i+2)      : 0);
      for(var j = 0; j < 4; j++)
      {
        if(i * 8 + j * 6 > input.length * 8) output += b64pad;
        else output += tab.charAt((triplet >>> 6*(3-j)) & 0x3F);
      }
    }
    return output;
  },

  /*
   * Convert a raw string to an arbitrary string encoding
   */
  rstr2any : function(input, encoding) {
    var divisor = encoding.length;
    var remainders = Array();
    var i, q, x, quotient;

    /* Convert to an array of 16-bit big-endian values, forming the dividend */
    var dividend = Array(Math.ceil(input.length / 2));
    for(i = 0; i < dividend.length; i++)
    {
      dividend[i] = (input.charCodeAt(i * 2) << 8) | input.charCodeAt(i * 2 + 1);
    }

    /*
     * Repeatedly perform a long division. The binary array forms the dividend,
     * the length of the encoding is the divisor. Once computed, the quotient
     * forms the dividend for the next step. We stop when the dividend is zero.
     * All remainders are stored for later use.
     */
    while(dividend.length > 0)
    {
      quotient = Array();
      x = 0;
      for(i = 0; i < dividend.length; i++)
      {
        x = (x << 16) + dividend[i];
        q = Math.floor(x / divisor);
        x -= q * divisor;
        if(quotient.length > 0 || q > 0)
          quotient[quotient.length] = q;
      }
      remainders[remainders.length] = x;
      dividend = quotient;
    }

    /* Convert the remainders to the output string */
    var output = "";
    for(i = remainders.length - 1; i >= 0; i--)
      output += encoding.charAt(remainders[i]);

    /* Append leading zero equivalents */
    var full_length = Math.ceil(input.length * 8 /
                                      (Math.log(encoding.length) / Math.log(2)))
    for(i = output.length; i < full_length; i++)
      output = encoding[0] + output;

    return output;
  },

  /*
   * Encode a string as utf-8.
   * For efficiency, this assumes the input is valid utf-16.
   */
  str2rstr_utf8 : function(input) {
    var output = "";
    var i = -1;
    var x, y;

    while(++i < input.length)
    {
      /* Decode utf-16 surrogate pairs */
      x = input.charCodeAt(i);
      y = i + 1 < input.length ? input.charCodeAt(i + 1) : 0;
      if(0xD800 <= x && x <= 0xDBFF && 0xDC00 <= y && y <= 0xDFFF)
      {
        x = 0x10000 + ((x & 0x03FF) << 10) + (y & 0x03FF);
        i++;
      }

      /* Encode output as utf-8 */
      if(x <= 0x7F)
        output += String.fromCharCode(x);
      else if(x <= 0x7FF)
        output += String.fromCharCode(0xC0 | ((x >>> 6 ) & 0x1F),
                                      0x80 | ( x         & 0x3F));
      else if(x <= 0xFFFF)
        output += String.fromCharCode(0xE0 | ((x >>> 12) & 0x0F),
                                      0x80 | ((x >>> 6 ) & 0x3F),
                                      0x80 | ( x         & 0x3F));
      else if(x <= 0x1FFFFF)
        output += String.fromCharCode(0xF0 | ((x >>> 18) & 0x07),
                                      0x80 | ((x >>> 12) & 0x3F),
                                      0x80 | ((x >>> 6 ) & 0x3F),
                                      0x80 | ( x         & 0x3F));
    }
    return output;
  },

  /*
   * Encode a string as utf-16
   */
  str2rstr_utf16le : function(input) {
    var output = "";
    for(var i = 0; i < input.length; i++)
      output += String.fromCharCode( input.charCodeAt(i)        & 0xFF,
                                    (input.charCodeAt(i) >>> 8) & 0xFF);
    return output;
  },

  str2rstr_utf16be : function(input) {
    var output = "";
    for(var i = 0; i < input.length; i++)
      output += String.fromCharCode((input.charCodeAt(i) >>> 8) & 0xFF,
                                     input.charCodeAt(i)        & 0xFF);
    return output;
  },

  /*
   * Convert a raw string to an array of big-endian words
   * Characters >255 have their high-byte silently ignored.
   */
  rstr2binb : function(input) {
    var output = Array(input.length >> 2);
    for(var i = 0; i < output.length; i++)
      output[i] = 0;
    for(var i = 0; i < input.length * 8; i += 8)
      output[i>>5] |= (input.charCodeAt(i / 8) & 0xFF) << (24 - i % 32);
    return output;
  },

  /*
   * Convert an array of big-endian words to a string
   */
  binb2rstr : function(input) {
    var output = "";
    for(var i = 0; i < input.length * 32; i += 8)
      output += String.fromCharCode((input[i>>5] >>> (24 - i % 32)) & 0xFF);
    return output;
  },

  /*
   * Main sha256 function, with its support functions
   */
  sha256_S : function(X, n) { return ( X >>> n ) | (X << (32 - n)); },
  sha256_R : function(X, n) { return ( X >>> n ); },
  sha256_Ch : function(x, y, z) { return ((x & y) ^ ((~x) & z)); },
  sha256_Maj : function(x, y, z) { return ((x & y) ^ (x & z) ^ (y & z)); },
  sha256_Sigma0256 : function(x) { return (sha256.sha256_S(x, 2) ^ sha256.sha256_S(x, 13) ^ sha256.sha256_S(x, 22)); },
  sha256_Sigma1256 : function(x) { return (sha256.sha256_S(x, 6) ^ sha256.sha256_S(x, 11) ^ sha256.sha256_S(x, 25)); },
  sha256_Gamma0256 : function(x) { return (sha256.sha256_S(x, 7) ^ sha256.sha256_S(x, 18) ^ sha256.sha256_R(x, 3)); },
  sha256_Gamma1256 : function(x) { return (sha256.sha256_S(x, 17) ^ sha256.sha256_S(x, 19) ^ sha256.sha256_R(x, 10)); },
  sha256_Sigma0512 : function(x) { return (sha256.sha256_S(x, 28) ^ sha256.sha256_S(x, 34) ^ sha256.sha256_S(x, 39)); },
  sha256_Sigma1512 : function(x) { return (sha256.sha256_S(x, 14) ^ sha256.sha256_S(x, 18) ^ sha256.sha256_S(x, 41)); },
  sha256_Gamma0512 : function(x) { return (sha256.sha256_S(x, 1)  ^ sha256.sha256_S(x, 8) ^ sha256.sha256_R(x, 7)); },
  sha256_Gamma1512 : function(x) { return (sha256.sha256_S(x, 19) ^ sha256.sha256_S(x, 61) ^ sha256.sha256_R(x, 6)); },

  sha256_K : new Array
  (
    1116352408, 1899447441, -1245643825, -373957723, 961987163, 1508970993,
    -1841331548, -1424204075, -670586216, 310598401, 607225278, 1426881987,
    1925078388, -2132889090, -1680079193, -1046744716, -459576895, -272742522,
    264347078, 604807628, 770255983, 1249150122, 1555081692, 1996064986,
    -1740746414, -1473132947, -1341970488, -1084653625, -958395405, -710438585,
    113926993, 338241895, 666307205, 773529912, 1294757372, 1396182291,
    1695183700, 1986661051, -2117940946, -1838011259, -1564481375, -1474664885,
    -1035236496, -949202525, -778901479, -694614492, -200395387, 275423344,
    430227734, 506948616, 659060556, 883997877, 958139571, 1322822218,
    1537002063, 1747873779, 1955562222, 2024104815, -2067236844, -1933114872,
    -1866530822, -1538233109, -1090935817, -965641998
  ),

  binb_sha256 : function(m, l) {
    var HASH = new Array(1779033703, -1150833019, 1013904242, -1521486534,
                         1359893119, -1694144372, 528734635, 1541459225);
    var W = new Array(64);
    var a, b, c, d, e, f, g, h;
    var i, j, T1, T2;

    /* append padding */
    m[l >> 5] |= 0x80 << (24 - l % 32);
    m[((l + 64 >> 9) << 4) + 15] = l;

    for(i = 0; i < m.length; i += 16)
    {
      a = HASH[0];
      b = HASH[1];
      c = HASH[2];
      d = HASH[3];
      e = HASH[4];
      f = HASH[5];
      g = HASH[6];
      h = HASH[7];

      for(j = 0; j < 64; j++)
      {
        if (j < 16) W[j] = m[j + i];
        else W[j] = sha256.safe_add(sha256.safe_add(sha256.safe_add(sha256.sha256_Gamma1256(W[j - 2]), W[j - 7]),
                                              sha256.sha256_Gamma0256(W[j - 15])), W[j - 16]);

        T1 = sha256.safe_add(sha256.safe_add(sha256.safe_add(sha256.safe_add(h, sha256.sha256_Sigma1256(e)), sha256.sha256_Ch(e, f, g)),
                                                            sha256.sha256_K[j]), W[j]);
        T2 = sha256.safe_add(sha256.sha256_Sigma0256(a), sha256.sha256_Maj(a, b, c));
        h = g;
        g = f;
        f = e;
        e = sha256.safe_add(d, T1);
        d = c;
        c = b;
        b = a;
        a = sha256.safe_add(T1, T2);
      }

      HASH[0] = sha256.safe_add(a, HASH[0]);
      HASH[1] = sha256.safe_add(b, HASH[1]);
      HASH[2] = sha256.safe_add(c, HASH[2]);
      HASH[3] = sha256.safe_add(d, HASH[3]);
      HASH[4] = sha256.safe_add(e, HASH[4]);
      HASH[5] = sha256.safe_add(f, HASH[5]);
      HASH[6] = sha256.safe_add(g, HASH[6]);
      HASH[7] = sha256.safe_add(h, HASH[7]);
    }
    return HASH;
  },

  safe_add : function(x, y){
    var lsw = (x & 0xFFFF) + (y & 0xFFFF);
    var msw = (x >> 16) + (y >> 16) + (lsw >> 16);
    return (msw << 16) | (lsw & 0xFFFF);
  }
}