package springboot_backend.config;

import org.springframework.beans.factory.annotation.Value;
import org.springframework.context.annotation.Configuration;
import org.springframework.web.servlet.config.annotation.CorsRegistry;
import org.springframework.web.servlet.config.annotation.ResourceHandlerRegistry;
import org.springframework.web.servlet.config.annotation.WebMvcConfigurer;

import java.io.File;
import java.nio.file.Paths;

@Configuration
public class WebConfig implements WebMvcConfigurer {

    @Value("${app.upload.dir:uploads/images}")
    private String uploadDir;

    @Override
    public void addCorsMappings(CorsRegistry registry) {
        registry.addMapping("/**")
                .allowedOriginPatterns("*")
                .allowedMethods("GET", "POST", "PUT", "DELETE", "OPTIONS", "HEAD", "PATCH")
                .allowedHeaders("*")
                .allowCredentials(true)
                .maxAge(3600);
    }

    @Override
    public void addResourceHandlers(ResourceHandlerRegistry registry) {
        String absoluteUploadPath = Paths.get(uploadDir).toAbsolutePath().toUri().toString();
        
        // Also locate legacy images if present
        File legacyAdminImages = new File("../backend_php_backup/admin/assets/images");
        String legacyUri = legacyAdminImages.toURI().toString();

        registry.addResourceHandler("/assets/images/**")
                .addResourceLocations(
                        absoluteUploadPath.endsWith("/") ? absoluteUploadPath : absoluteUploadPath + "/",
                        legacyUri.endsWith("/") ? legacyUri : legacyUri + "/",
                        "classpath:/static/assets/images/",
                        "file:uploads/images/"
                );

        registry.addResourceHandler("/uploads/**")
                .addResourceLocations(
                        absoluteUploadPath.endsWith("/") ? absoluteUploadPath : absoluteUploadPath + "/",
                        "file:uploads/"
                );

        registry.addResourceHandler("/**")
                .addResourceLocations("classpath:/static/");
    }
}
